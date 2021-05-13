<?php

namespace Drupal\io_util\Commands;

use Drupal;
use Drush\Commands\DrushCommands;


/**
 * Drush commands for post import and export
 *
 * @package Drupal\io_util\Commands
 */

class IoCommands extends DrushCommands {
  /**
   *
   * This command exports a given Drupal post by ID to a given file.
   *
   * @param $postId
   *   ID for post to export
   * @param $saveFile
   *   Filename to export ID to
   *
   * @command io_util:exportOne
   * @aliases ioutil-exportOne
   *
   * @usage io_util:exportOne 17 drupal_post_17.json
   *   Creates a file called "drupal_post_17.json" or rewrites it, and puts in a json representation of a Drupal post with ID of 17
   */
  public function exportOne($postId, $saveFile) {
    $exportService = Drupal::service('io_util.node_exporter');
    $exportService->generateSaveFile($postId, $saveFile);
  }

  /**
   *
   * This command import a given Drupal post by ID to a given file.
   *
   * @param $saveFile
   *   Filename to export ID to
   *
   * @command io_util:importOne
   * @aliases ioutil-importOne
   * @option wptf
   *   Whether or not to transform imported WordPress content into compatible Drupal content
   *
   * @usage io_util:importOne drupal_post_17.json
   *   Reads a file called "drupal_post_17.json", and puts saved information into a new Drupal post
   */
  public function importOne($saveFile, $options = ['wptf' => FALSE]) {
    if(!$options['wptf']) {
      $importService = Drupal::service('io_util.node_importer');
      $importService->importSaveFile($saveFile);
    }
    else {
      echo 'Will transform!';
    }
  }

  /**
   *
   * This command exports all available Drupal posts into a given file directory.
   *
   * @param $saveDirectory
   *   Directory within which to place saved files
   *
   * @command io_util:exportAll
   * @aliases ioutil-exportAll
   *
   * @usage io_util:exportAll content_type /srv/export/mass_export
   *   Populates /srv/export/mass_export with exported files of available Drupal posts
   */
  public function exportAll($contentType, $saveDirectory) {
    $warningComp = [];
    if(!file_exists($saveDirectory)){
      echo 'Directory not found; attempting construction...'."\n";
      mkdir($saveDirectory);
      echo 'Directory made!'."\n";
    }
    if(substr($saveDirectory,-1) != '/') {
      $saveDirectory = $saveDirectory.'/';
    }
    $postIds = array_keys(Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => $contentType]));
    $exportService = Drupal::service('io_util.node_exporter');
    foreach($postIds as $postId) {
      $saveFile = $saveDirectory."drupal-".$postId.".json";
      $warnings = $exportService->generateSaveFile($postId, $saveFile);
      // echo $saveFile."\n";
      foreach($warnings as $warning) {
        $warningComp[$warning][] = $postId;
      }
    }
    echo "\n---\n";
    foreach($warningComp as $key=>$value){
      echo $key;
      echo implode(',',$value);
      echo "\n";
    }
    if( count($warningComp) == 0 ) {
      echo "\nCompleted without error.";
    }
    echo "\nExport written to " . $saveDirectory . "\n";
  }

  /**
   *
   * This command imports all saved Drupal posts within a given file directory.
   *
   * @param $saveDirectory
   *   Directory within which to find saved files
   *
   * @command io_util:importAll
   * @aliases ioutil-importAll
   *
   * @usage io_util:importAll /srv/export/mass_export
   *   Reads /srv/export/mass_export for exported files to make new Drupal posts
   */
  public function importAll($saveDirectory) {
    if(!file_exists($saveDirectory)){
      echo 'Directory not found; please provide a valid directory.'."\n";
      return;
    }
    $glob = "*.json";
    if(substr($saveDirectory,-1) != '/') {
      $saveDirectory = $saveDirectory.'/';
    }
    $search = $saveDirectory.$glob;

    $saveFiles = glob($search);
    $importService = Drupal::service('io_util.node_importer');
    $redirectMap = '';
    foreach($saveFiles as $saveFile) {
      echo "\nImporting ".$saveFile."... ";
      $urls = $importService->importSaveFile($saveFile);
      if( $urls['old'] != $urls['new'] ) {
        $redirectMap .= $urls['old'] . ' ' . $urls['new'] . "\n";
      }
    }
    echo "\n----START MODIFIED URL REDIRECT MAP----\n";
    echo $redirectMap;
    echo '----END MODIFIED URL REDIRECT MAP----';
    echo "\nDone. Check for errors in the output above.\n";

  }


}

<?php

namespace Drupal\io_utils\Commands;

use Drupal;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;


/**
 * Drush commands for post import and export
 *
 * @package Drupal\io_utils\Commands
 */

class IoCommands extends DrushCommands {

    /**
     * Search all active entities for a regular expression
     * @param string $search The regular expression to search for, e.g. /^foo-(.*)-baz$/
     * @option field-names Optional comma-separated list of entity types to search
     * @option unpublished Optional flag to search unpublished (e.g. draft/archived) entities
     * @command io-utils:search
     * @usage io-utils:search "/example/" [--field-names body,field_example]
     *   Searches for the given regex expression in all active fields of all published entities
     */
    public function search(string $search, $options = ['field-names' => NULL, 'unpublished' => FALSE]) {

        $fieldNames = [];
        if( !empty($options['field-names']) ) {
            $fieldNames = explode(',', $options['field-names']);
        }
        $searchService = new Drupal\io_utils\Services\SearchAndReplace($this->output);
        ob_start();
        $count = $searchService->findByRegex($search, $fieldNames, $options['unpublished']);
        $results = ob_get_clean();

        $this->io()->writeln($results);
        $this->io()->success("Your search term was found in $count entities.");
    }

    /**
     * Search and replace all active entities for a regular expression with a replacement, allowing back references
     * @param string $search The regular expression to search for, e.g. /^foo-(.*)-baz$/
     * @param string $replace The replacement string, e.g. "bar-$1-baz"
     * @option field-names Optional comma-separated list of entity types to search/replace
     * @command io-utils:replace
     * @usage io-utils:replace "/^foo-(.*)-baz$/" "bar-$1-baz" --field-names body,field_example
     *   Searches for the given regex expression in all active fields of all published entities, and replaces it with the given replacement string
     */
    public function replace(string $search, string $replace, $options = ['field-names' => NULL]) {

        if (!$this->io()->confirm(dt('Are you sure you wish to continue with a global search and replace (you should back up the DB first)?'))) {
            throw new UserAbortException();
        }

        $fieldNames = [];
        if( !empty($options['field-names']) ) {
            $fieldNames = explode(',', $options['field-names']);
        }
        $searchService = new Drupal\io_utils\Services\SearchAndReplace($this->output);
        ob_start();
        $count = $searchService->replaceByRegex($search, $replace, $fieldNames);
        $results = ob_get_clean();

        $this->io()->writeln($results);
        $this->io()->success("Your search term was replaced in $count entities.");
    }


  /**
   * Exports an entity by ID to a JSON file.
   *
   * @param $postId
   *   ID for post to export
   * @param $saveFile
   *   Filename to export ID to
   * @param $bPublishedOnly
   *   Boolean flag indicates if only published nodes can be exported or not
   *
   * @command io-utils:export-one
   *
   * @usage io-utils:export-one 17 drupal_post_17.json 0
   *   Creates a file called "drupal_post_17.json" or rewrites it, and puts in a json representation of a Drupal post with ID of 17
   */
  public function exportOne($postId, $saveFile, $bPublishedOnly=true) {
    $exportService = Drupal::service('io_utils.node_exporter');
    $exportService->generateSaveFile($postId, $saveFile, $bPublishedOnly);
  }

  /**
   * Exports a block content by ID to a JSON file.
   *
   * @param $blockContentId
   *   ID for block content to export
   * @param $saveFile
   *   Filename to export ID to
   *
   * @command io-utils:export-block-content
   *
   * @usage io-utils:export-block-content 17 drupal_block_content_17.json
   *   Creates a file called "drupal_block_content_17.json" or rewrites it, and puts in a json representation of a Drupal block with ID of 17
   */
  public function exportBlockContent($blockContentId, $saveFile) {
    $exportService = Drupal::service('io_utils.block_content_exporter');
    $exportService->generateSaveFile($blockContentId, $saveFile);
  }

  /**
   * Imports block content from a JSON file.
   *
   * @param $saveFile
   *   Filename to import block content from
   *
   * @command io-utils:import-one-block-content
   * @option wptf
   *   Whether or not to transform imported WordPress content into compatible Drupal content
   *
   * @usage io-utils:import-one-block-content /srv/export/block-17.json
   * Reads a file called "block-17.json", and puts saved information into a new Drupal block content
   */
  public function importOneBlockContent($saveFile, $options = ['wptf' => FALSE]) {
    if(!file_exists($saveFile)){
      echo 'File not found; please provide a valid import file.'."\n";
      return;
    }
    $saveDirectory = dirname($saveFile);

    if(!$options['wptf']) {
      $importService = Drupal::service('io_utils.block_content_importer');
      echo "\nImporting block content " . $saveFile . "... ";
      $importService->importBlockContentSaveFile($saveDirectory, $saveFile);
    }
    else {
      echo 'Will transform!';
    }
  }

  /**
   * Imports all saved block content within a directory.
   *
   * @param $saveDirectory
   *   Directory within which to find saved files
   *
   * @command io-utils:import-all-block-content
   *
   * @usage io-utils:import-all-block-content /srv/export/mass_export
   *   Reads /srv/export/mass_export for exported files to make new Drupal block content
   */
  public function importAllBlockContent($saveDirectory) {
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

    $importService = Drupal::service('io_utils.block_content_importer');
    foreach($saveFiles as $saveFile) {
      echo "\nImporting block content " . $saveFile . "... ";
      $importService->importBlockContentSaveFile($saveDirectory, $saveFile);
    }
    echo "\nDone. Check for errors in the output above.\n";
  }

  /**
   * Import an entity from a JSON file.
   *
   * @param $saveFile
   *   Filename of JSON to import
   *
   * @command io-utils:import-one
   * @option wptf
   *   Whether or not to transform imported WordPress content into compatible Drupal content
   *
   * @usage io-utils:import-one drupal_post_17.json
   *   Reads a file called "drupal_post_17.json", and puts saved information into a new Drupal post
   */
  public function importOne($saveFile, $options = ['wptf' => FALSE]) {
    if(!$options['wptf']) {
      $importService = Drupal::service('io_utils.node_importer');
      $importService->importSaveFile($saveFile);
    }
    else {
      echo 'Will transform!';
    }
  }

  /**
   * Exports all entities to JSON files in a directory.
   *
   * @param $saveDirectory
   *   Directory within which to place saved files
   *
   * @command io-utils:export-all
   *
   * @usage io-utils:export-all content_type /srv/export/mass_export
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
    $exportService = Drupal::service('io_utils.node_exporter');
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
   * Imports all entities from JSON files from a directory.
   *
   * @param $saveDirectory
   *   Directory within which to find saved files
   *
   * @command io-utils:import-all
   *
   * @usage io-utils:import-all /srv/export/mass_export
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
    $importService = Drupal::service('io_utils.node_importer');
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

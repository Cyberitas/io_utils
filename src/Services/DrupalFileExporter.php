<?php


namespace Drupal\io_utils\Services;


use Drupal;

class DrupalFileExporter
{

  public static function exportFile($mediaFolder, $values) {
    if(!file_exists($mediaFolder)){
      mkdir($mediaFolder, 0777, true);
    }
    $files = [];
    foreach($values->getIterator() as $key=>$value) {
      $target_id = $value->target_id;
      $imageEntity = Drupal::entityTypeManager()->getStorage('file')->load($target_id);
      if(!$imageEntity){
        echo 'Image at id '.$target_id.' could not be found- terminating program.';
        exit(1);
      }
      $uri = $imageEntity->get('uri')->get(0)->getString();
      $path = Drupal::service('file_system')->realpath($uri);
      $basename = basename($path);

      $saveName = $mediaFolder.$basename;
      $fileHash = sha1_file($path);
      $suffixNum = 2;
      $makeNew = true;

      while(file_exists($saveName)) {
        $newHash = sha1_file($saveName);
        #Check if existing file is the one to be saved
        if($fileHash == $newHash) {
          // echo 'Attached image found!'."\n";
          $makeNew = false;
          break;
        }
        else {
          $saveName = $mediaFolder . $basename . $suffixNum;
          $suffixNum = $suffixNum + 1;
        }
      }
      if($makeNew) {
        // echo 'Saving new image to '.$saveName."\n";
        echo ' Exported FID ' . $target_id . '...';
        copy($path, $saveName);
      }
      $saveItem = [
        'uri' => $uri,
        'path' => $saveName
      ];
      $files[] = $saveItem;
    }
    return $files;
  }

}

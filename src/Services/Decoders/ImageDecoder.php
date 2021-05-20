<?php


namespace Drupal\io_utils\Services\Decoders;

use Drupal;
use Drupal\Core\File\FileSystemInterface;

class ImageDecoder extends AbstractFieldDecoder implements FieldDecoderInterface
{
  /**
   * @var bool $allowDuplicateImages Whether to allow images to be re-imported even if they match images already in
   * the system.  If set to false, images will not be duplicated.  If set to true, images will be re-uploaded, even
   * if they were already found.
   */
  private $allowDuplicateImages = false;

  /**
   * @return bool
   */
  public function isAllowDuplicateImages(): bool
  {
    return $this->allowDuplicateImages;
  }

  /**
   * @param bool $allowDuplicateImages
   */
  public function setAllowDuplicateImages(bool $allowDuplicateImages): void
  {
    $this->allowDuplicateImages = $allowDuplicateImages;
  }


  public function decodeItems($encodedValue) {
    $items = $encodedValue['items'];
    $decoded = [];

    foreach($items as $item) {
      $decoded[] = $this::decodeItem($item);
    }

    return $decoded;
  }

  /**
   * @inheritDoc
   */
  public function decodeItem($value)
  {
    $uri = $value['uri'];
    $importPath = $value['path'];
    $oldId = $value['target_id'];

    if( empty($importPath) ) {
      return null;
    }

    if( substr($importPath, 0, 2) == './' ) {
      $importPath = $this->importFolder . '/' . substr($importPath, 2);
    }

    $importHash = sha1_file($importPath);

    echo "\n  Searching for image $oldId... ";
    $unconfirmedImageEntity = Drupal::entityTypeManager()->getStorage('file')->load($oldId);
    if($unconfirmedImageEntity) {
      $unconfirmedUri = $unconfirmedImageEntity->get('uri')->get(0)->getString();

      if ($unconfirmedUri == $uri) {
        //further checks that they're the same
        $unconfirmedPath = Drupal::service('file_system')->realpath($unconfirmedUri);
        $unconfirmedHash = sha1_file($unconfirmedPath);

        if ($unconfirmedHash == $importHash) {
          if( !$this->isAllowDuplicateImages() ) {
            echo "Skipping image $uri, match type 1 found...";
            return [
              'target_id' => $value['target_id'],
              'alt' => $value['alt'],
              'title' => $value['title'],
              'width' => $value['width'],
              'height' => $value['height'],
            ];
          }
        }
      }
    }


    $baseUri = $uri;
    $version = 1;
    while(file_exists($uri)) {
      $files = Drupal::entityTypeManager()->getStorage('file')->loadByProperties(['uri' => $uri]);
      $file = reset($files);
      if($file){
        $uri = $file->get('uri')->get(0)->getString();
        $storedPath = Drupal::service('file_system')->realpath($uri);
        $storedHash = sha1_file($storedPath);
        if($storedHash == $importHash){
          if( !$this->isAllowDuplicateImages() ) {
            echo "Skipping image import of $uri, match type 2 found...";

            // echo 'Matched image found!'."\n";
            $newId = $file->id();
            // echo 'Found id is '.$newId."\n";
            return [
              'target_id' => $newId,
              'alt' => $value['alt'],
              'title' => $value['title'],
              'width' => $value['width'],
              'height' => $value['height'],
            ];
          }
        }
      }
      $version++;
      $uri = $baseUri.'_'.$version;
    }

    $uri = $baseUri;
    echo "No match found, importing new... ";
    //  echo 'Image match not found- making one. Importing from '.$importPath.". ";
    //behavior that if the new file does not exist
    $fileData = file_get_contents($importPath);

    if( $fileData === false ) {
      echo "IMAGE CONTENT NOT FOUND! $importPath";
    }

    // echo 'Contents imported, saving to '.$uri.'...'.". ";

    $fileSaved = file_save_data($fileData, $uri, FileSystemInterface::EXISTS_RENAME);
    // echo 'Save attempted...'."\n";
    if(!$fileSaved){
      echo 'Error saving image.'."\n";
      echo "\n****CRITICAL FAILURE: MEDIA COULD NOT BE SAVED TO $uri****\n";

      return [
        'target_id' => null,
        'alt' => $value['alt'],
        'title' => $value['title'],
        'width' => $value['width'],
        'height' => $value['height'],
      ];
    }
    $newId = $fileSaved->id();
    echo 'New image ID '.$newId."...";
    return [
      'target_id' => $newId,
      'alt' => $value['alt'],
      'title' => $value['title'],
      'width' => $value['width'],
      'height' => $value['height'],
    ];
  }
}

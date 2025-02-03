<?php
namespace Drupal\io_utils\Services;

use Drupal;
use Drupal\block_content\Entity\BlockContent;
use Drupal\io_utils\Services\Decoders\FieldDecoderInterface;
use Drupal\io_utils\Services\Decoders\GenericDecoder;

class DrupalBlockContentImporter
{
  /**
   * Import block content definitions from the file.
   *
   * @param $baseDir
   * @param $filename
   * @return BlockContent|null
   * @throws Drupal\Core\Entity\EntityStorageException
   */
  public function importBlockContentSaveFile($baseDir, $filename)
  {
    if (!file_exists($filename)) {
      echo "ERROR: Block content definition not found at " . $filename . ", import failed.\n";
      return null;
    }

    /** @var DrupalNodeImporter $nodeImporter */
    $nodeImporter = Drupal::service('io_utils.node_importer');

    // $importFolder = dirname($filename);
    $serialized = file_get_contents($filename);
    $saveFormat = new ContentProcessors\ContentItem();
    $saveFormat->unserialize($serialized);

    // Load all the block content items and check if title already exists. Display message and return.
    $blockContentItems = Drupal::entityQuery('block_content')->accessCheck(FALSE)->condition('info', $saveFormat->getTitle())->execute();
    if($blockContentItems && sizeof($blockContentItems) > 0) {
      echo "ERROR: Block content already exists with description [" . $saveFormat->getTitle() . "], import failed.\n";
      return null;
    }

    // Import dependent media
    if( $saveFormat->getInlineMedia() && is_array( $saveFormat->getInlineMedia() ) && count( $saveFormat->getInlineMedia() ) > 0 ) {
      foreach( $saveFormat->getInlineMedia() as $mediaItem ) {
        $nodeImporter->importInlineMediaItem( $mediaItem, $baseDir );
      }
    }

    /** @var BlockContent $blockContent */
    $blockContent = BlockContent::create([
      'type' => $saveFormat->getPostType(),
      'info' => $saveFormat->getTitle(),
    ]);

    echo "\n    Importing block content item: " . $saveFormat->getTitle() . "...";

    foreach ($blockContent->getFields() as $key => $values) {
      $definition = Drupal::service('entity_field.manager')->getFieldDefinitions('block_content', $blockContent->bundle())[$key];
      if ($definition->getTargetBundle()) {
        $realType = $definition->getType();

        $encodedValue = null;
        $savedType = $realType;
        if (isset($saveFormat->getAttachedData()[$key])) {
          $encodedValue = $saveFormat->getAttachedData()[$key];
          $savedType = $encodedValue['type'];
        }
        $typeMismatch = false;
        if($realType != $savedType) {
          $typeMismatch = true;
        }

        if($typeMismatch && (class_exists('Drupal\\io_utils\\Services\\Decoders\\' . ucfirst($savedType) . 'Decoder'))){
          $decoderClass = 'Drupal\\io_utils\\Services\\Decoders\\' . ucfirst($savedType) . 'Decoder';
          if (class_exists($decoderClass)) {
            /** @var FieldDecoderInterface $decoder */
            $decoder = new $decoderClass;
          } else {
            echo 'Warning, no decoder: ' . $realType . "\n";
            /** @var FieldDecoderInterface $decoder */
            $decoder = new GenericDecoder();
          }
        }
        else {
          $decoderClass = 'Drupal\\io_utils\\Services\\Decoders\\' . ucfirst($realType) . 'Decoder';
          if (class_exists($decoderClass)) {
            /** @var FieldDecoderInterface $decoder */
            $decoder = new $decoderClass;
          } else {
            echo 'Warning, no decoder: ' . $realType . "\n";
            /** @var FieldDecoderInterface $decoder */
            $decoder = new GenericDecoder();
          }
        }
        $decoder->setImportFolder( $baseDir );

        $blockContent->set($key, $decoder->decodeItems($encodedValue));
      }
    }

    // We preserve block's UUID for embedded drupal-entity and block placement use!
    if (isset($saveFormat->getAttachedData()['uuid']['items'][0]['value']) &&
      !empty($saveFormat->getAttachedData()['uuid']['items'][0]['value']) ) {
      $uuid = $saveFormat->getAttachedData()['uuid']['items'][0]['value'];

      $count = \Drupal::entityTypeManager()->getStorage('block_content')->loadByProperties(['uuid' => $uuid]);
      if (count($count) > 0) {
        echo "Warning, Block UUID $uuid already exists, not setting uuid.\n";
      } else {
        $blockContent->set('uuid', $uuid);
      }
    }

    $blockContent->isNew();
    $blockContent->save();
    echo " New block content created at ID " . $blockContent->id() . "\n";
    return $blockContent;
  }
}

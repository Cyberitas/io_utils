<?php
namespace Drupal\io_utils\Services;

use Drupal;
use Drupal\io_utils\Services\Decoders\FieldDecoderInterface;
use Drupal\io_utils\Services\Decoders\GenericDecoder;

class DrupalMediaImporter
{
  /**
   * @param $baseDir
   * @param $filename
   * @return Drupal\media\Entity\Media|null
   * @throws Drupal\Core\Entity\EntityStorageException
   */
  public function importMediaSaveFile($baseDir, $filename)
  {
    if (!file_exists($filename)) {
      echo "Media definition not found at " . $filename . ", import failed.\n";
      return null;
    }

    /** @var DrupalNodeImporter $nodeImporter */
    $nodeImporter = Drupal::service('io_utils.node_importer');

    $serialized = file_get_contents($filename);
    $saveFormat = new ContentProcessors\ContentItem();
    $saveFormat->unserialize($serialized);


    // Check if media already exists. Display message and return if so.
    if (isset($saveFormat->getAttachedData()['uuid']['items'][0]['value']) &&
      !empty($saveFormat->getAttachedData()['uuid']['items'][0]['value']) ) {
      $uuid = $saveFormat->getAttachedData()['uuid']['items'][0]['value'];

      $count = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['uuid' => $uuid]);
      if (count($count) > 0) {
        echo "Warning, Media UUID $uuid already exists, skipping media import.\n";
        return null;
      }
    }


    // Import dependent media
    if( $saveFormat->getInlineMedia() && is_array( $saveFormat->getInlineMedia() ) && count( $saveFormat->getInlineMedia() ) > 0 ) {
      foreach( $saveFormat->getInlineMedia() as $mediaItem ) {
        $nodeImporter->importInlineMediaItem( $mediaItem, $baseDir );
      }
    }

    $media = Drupal\media\Entity\Media::create([
      'bundle' => $saveFormat->getPostType(),
      //'title' => $saveFormat->getTitle(),
    ]);

    if( !empty( $saveFormat->getTitle() ) ) {
      $media->setName($saveFormat->getTitle());
    }

    if( !empty( $saveFormat->getAuthor() ) && isset($saveFormat->getAuthor()['items'][0]['name']) ) {
      $authorName = $saveFormat->getAuthor()['items'][0]['name'];
      $author = user_load_by_name($authorName);
      if($author) {
        $authorId = $author->id();
        $media->set('uid', $authorId);
      }
    }

    foreach ($media->getFields() as $key => $values) {
      $definition = Drupal::service('entity_field.manager')->getFieldDefinitions('media', $media->bundle())[$key];
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

        $media->set($key, $decoder->decodeItems($encodedValue));
      }
    }


    // We preserve media's UUID for embedded drupal-media WYSIWYG use!
    if (isset($saveFormat->getAttachedData()['uuid']['items'][0]['value']) &&
      !empty($saveFormat->getAttachedData()['uuid']['items'][0]['value']) ) {
      $uuid = $saveFormat->getAttachedData()['uuid']['items'][0]['value'];

      $count = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['uuid' => $uuid]);
      if (count($count) > 0) {
        echo "Warning, Media UUID $uuid already exists, not setting uuid.\n";
      } else {
        $media->set('uuid', $uuid);
      }
    }


    //$media->setPublished();
    $media->isNew();
    $media->save();
    // echo "    New Media created at ID " . $media->id();
    return $media;
  }

}

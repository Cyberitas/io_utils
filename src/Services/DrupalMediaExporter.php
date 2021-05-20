<?php
namespace Drupal\io_utils\Services;
use Drupal;
use Drupal\io_utils\Services\Encoders\AuthorEncoder;


class DrupalMediaExporter
{

  public function generateSaveFile($mediaItem)
  {
    $filename = null;
    if( !file_exists(DrupalExportUtils::$exportFolder . '/attached_media') ) {
      mkdir( DrupalExportUtils::$exportFolder . '/attached_media' );
    }
    if($mediaItem) {
      $filename = 'attached_media/media-'.$mediaItem->id().'.json';
      $fullPath = DrupalExportUtils::$exportFolder . '/' . $filename;
      echo "\n    Exporting media item: ".$mediaItem->id()."...";
      $saveFormat = new ContentProcessors\ContentItem();
      $saveFormat->setFormat('drupal-media');
      $saveFormat->setId($mediaItem->id());
      $postDate = date( 'Y-m-d H:i:s', $mediaItem->getCreatedTime());
      $saveFormat->setDate( $postDate );
      $saveFormat->setPostType($mediaItem->bundle());

      $saveFormat->setTitle( $mediaItem->get('name')->value );

      $definitions = Drupal::service('entity_field.manager')->getFieldDefinitions('media', $mediaItem->bundle());

      $authorEncoder = new AuthorEncoder();
      $saveFormat->setAuthor($authorEncoder->encodeItems( $definitions['uid'], $mediaItem->get('uid')) );

      $attachedData = array();
      $attachedData['name'] = DrupalExportUtils::encodeField($definitions['name'], $mediaItem, 'name', $mediaItem->get('name'));
      $attachedData['path'] = DrupalExportUtils::encodeField($definitions['path'], $mediaItem, 'path', $mediaItem->get('path'));
      foreach($mediaItem->getFields() as $key=>$values) {
        $definition = $definitions[$key];
        if($definition->getTargetBundle()) {
          $attachedData[$key] = DrupalExportUtils::encodeField($definition, $mediaItem, $key, $values);
        }
      }

      $saveFormat->setAttachedData($attachedData);
      file_put_contents($fullPath, $saveFormat->serialize());
    }
    return $filename;
  }


}

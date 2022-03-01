<?php

namespace Drupal\io_utils\Services;

use Drupal;
use Drupal\block_content\Entity\BlockContent;
use Exception;

class DrupalBlockContentExporter
{
  /**
   * Save the block content information to a JSON file.
   * @param $blockContentId
   * @param $filename
   * @return array
   * @throws Exception
   */
  public function generateSaveFile($blockContentId, $filename)
  {
    DrupalExportUtils::$exportFolder = dirname( $filename );
    echo "\n    Exporting block content item: " . $blockContentId . "...";
    $blockItem = BlockContent::load($blockContentId);
    if($blockItem) {
      $saveFormat = new ContentProcessors\ContentItem();
      $saveFormat->setFormat('drupal-block-content');
      $saveFormat->setId($blockItem->id());
      $saveFormat->setPostType($blockItem->bundle());
      $saveFormat->setTitle($blockItem->get('info')->value);
      $saveFormat->setDate(date('Y-m-d H:i:s', $blockItem->getRevisionCreationTime()));

      $definitions = Drupal::service('entity_field.manager')->getFieldDefinitions('block_content', $blockItem->bundle());
      $attachedData = array();
      foreach($blockItem->getFields() as $key=>$values) {
        $definition = $definitions[$key];
        if($definition->getTargetBundle()) {
          $attachedData[$key] = DrupalExportUtils::encodeField($definition, $blockItem, $key, $values);
        }
      }
      $attachedData['uuid'] = DrupalExportUtils::encodeField($definitions['uuid'], $blockItem, 'uuid', $blockItem->get('uuid'));
      $saveFormat->setAttachedData($attachedData);
      file_put_contents($filename, $saveFormat->serialize());
    } else {
      echo "N/A no block content with that ID";
      DrupalExportUtils::$warnings[] = "N/A no block content with that ID";
    }
    return DrupalExportUtils::$warnings;
  }
}

<?php

namespace Drupal\io_utils\Services\Encoders;

use Drupal\Core\Field\FieldItemList;
use Drupal\io_utils\Services\DrupalHtmlParser;
use Drupal\io_utils\Services\DrupalMediaExporter;

abstract class AbstractTextWithEmbedEncoder implements FieldEncoderInterface
{
  /**
   * @var array $embeddedEntities
   */
  protected $embeddedEntities;

  /**
   * @param $definition
   * @param FieldItemList $values
   * @return array
   * @throws \Exception
   */
  public function encodeItems($definition, FieldItemList $values) {
    $returnValue = [
      'type' => $definition->getType(),
      'items' => [],
    ];

    foreach($values->getIterator() as $key=>$val) {
      $returnValue['items'][$key] = $this->encodeItem($val);
    }

    if( !empty($this->embeddedEntities) ) {
      $returnValue['embeddedEntities'] = $this->embeddedEntities;
    }

    return $returnValue;
  }

  /**
   * @inheritDoc
   */
  public function encodeItem($value)
  {
    $response = [];
    foreach( $value->getFieldDefinition()->getFieldStorageDefinition()->getColumns() as $key => $column ) {
      if( $key == 'value' && $column['type'] == 'text' ) {
        $this->extractEmbeddedEntities($value->$key);
      }
      $response[$key] = $value->$key;
    }

    return $response;
  }

  protected function extractEmbeddedEntities( $html ) {
    $this->embeddedEntities = null;
    $htmlParser = new DrupalHtmlParser();
    $htmlParser->setHtml($html);
    // Right now, only media embed chaining is supported, any blocks must be done manually
    foreach( $htmlParser->getEmbeddedEntityTagAttribs( true, 'media' ) as $tagAttribs ) {
      $uuid = $tagAttribs['data-entity-uuid'];
      $referencedEntity = \Drupal::service('entity.repository')->loadEntityByUuid('media', $uuid);
      if ($referencedEntity !== null) {
        $mediaService = new DrupalMediaExporter();
        $saveFile = $mediaService->generateSaveFile($referencedEntity);
        if( $this->embeddedEntities == null ) $this->embeddedEntities = [];
        $this->embeddedEntities[] = [
          'type' => $tagAttribs['data-entity-type'],
          'export_file' => $saveFile
        ];

      } else {
        echo "\nWARNING: Could not find related media item to export with UUID " . $uuid . "...";
      }
    }

  }
}

<?php

namespace Drupal\io_utils\Services\Encoders;

use Drupal;
use Drupal\io_utils\Services\DrupalParagraphExporter;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;

class Entity_reference__mediaEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  /**
   * @inheritDoc
   * @param EntityReferenceRevisionsItem $value
   */
  public function encodeItem($value)
  {
    /** @var \Drupal\Core\Entity\EntityInterface $referencedEntity */
    $referencedEntity = $value->get('entity')->getValue();

    $referencedEntityType = $referencedEntity->getEntityTypeId();
    if( $referencedEntityType !== 'media' ) {
      $err = "Deep referenced item type mismatch.  Expected media, received $referencedEntityType\n";
      Drupal\io_utils\Services\DrupalExportUtils::$warnings[] = $err;
      echo $err;
    }

    $encodedValue = [
      'target_id' => $value->get('target_id')->getValue(),
      'referenced_entity' => [
        'type' => $referencedEntityType,
      ],
    ];

    $mediaService = new Drupal\io_utils\Services\DrupalMediaExporter();
    $saveFile = $mediaService->generateSaveFile($referencedEntity);
    if( $saveFile != null ) {
      $encodedValue['referenced_entity']['export_file'] = $saveFile;
    }

    return $encodedValue;
  }
}

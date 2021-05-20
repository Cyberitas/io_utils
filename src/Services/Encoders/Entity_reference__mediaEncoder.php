<?php

namespace Drupal\io_utils\Services\Encoders;

use Drupal;
use Drupal\io_utils\Services\DrupalParagraphExporter;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;

class Entity_reference__mediaEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  /**
   * @inheritDoc
   * @var $value EntityReferenceRevisionsItem
   */
  public function encodeItem($value)
  {
    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $entityAdapter */
    $entityAdapter = $value->get('entity')->getTarget();

    /** @var \Drupal\Core\Entity\EntityInterface $referencedEntity */
    $referencedEntity = $entityAdapter->getValue();

    $referencedEntityType = $referencedEntity->getEntityTypeId();
    if( $referencedEntityType !== 'media' ) {
      $err = "Deep referenced item type mismatch.  Expected media, received $referencedEntityType\n";
      Drupal\io_utils\Services\DrupalExportUtils::$warnings[] = $err;
      echo $err;
    }

    $encodedValue = [
      'target_id' => $value->target_id,
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

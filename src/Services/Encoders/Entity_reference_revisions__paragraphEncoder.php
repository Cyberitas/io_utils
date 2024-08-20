<?php

namespace Drupal\io_utils\Services\Encoders;

use Drupal;
use Drupal\io_utils\Services\DrupalParagraphExporter;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;

class Entity_reference_revisions__paragraphEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  /**
   * @inheritDoc
   * @param EntityReferenceRevisionsItem $value
   */
  public function encodeItem($value)
  {
    /** @var Drupal\paragraphs\Entity\Paragraph $referencedEntity */
    $referencedEntity = $value->get('entity')->getValue();

    $referencedEntityType = $referencedEntity->getEntityTypeId();
    if( $referencedEntityType !== 'paragraph' ) {
      $err = "Deep referenced item type mismatch.  Expected paragraph, received $referencedEntityType\n";
      Drupal\io_utils\Services\DrupalExportUtils::$warnings[] = $err;
      echo $err;
    }

    $encodedValue = [
      'target_id' => $value->get('target_id')->getValue(),
      'target_revision_id' => $value->get('target_revision_id')->getValue(),
      'referenced_entity' => [
        'type' => $referencedEntityType,
      ],
    ];

    $paragraphService = new Drupal\io_utils\Services\DrupalParagraphExporter();
    $saveFile = $paragraphService->generateSaveFile($referencedEntity);
    if( $saveFile != null ) {
      $encodedValue['referenced_entity']['export_file'] = $saveFile;
    }

    return $encodedValue;
  }
}

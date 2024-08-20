<?php

namespace Drupal\io_utils\Services\Encoders;

use Drupal;
use Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem;

class Entity_reference_revisionsEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{

  /**
   * @param Drupal\Core\Field\FieldItemInterface $value
   * @return array
   * @throws Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function encodeItem($value)
  {
    /** @var \Drupal\Core\Entity\EntityInterface $referencedEntity */
    $referencedEntity = $value->get('entity')->getValue();

    $referencedEntityType = $referencedEntity->getEntityTypeId();

    $encodedValue = [
      'target_id' => $value->get('target_id')->getValue(),
      'target_revision_id' => $value->get('target_revision_id')->getValue(),
      'referenced_entity' => [
        'type' => $referencedEntityType,
      ],
    ];

    return $encodedValue;
  }
}

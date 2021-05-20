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
    /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $entityAdapter */
    $entityAdapter = $value->get('entity')->getTarget();

    /** @var \Drupal\Core\Entity\EntityInterface $referencedEntity */
    $referencedEntity = $entityAdapter->getValue();

    $referencedEntityType = $referencedEntity->getEntityTypeId();

    $encodedValue = [
      'target_id' => $value->target_id,
      'target_revision_id' => $value->target_revision_id,
      'referenced_entity' => [
        'type' => $referencedEntityType,
      ],
    ];

    return $encodedValue;
  }
}

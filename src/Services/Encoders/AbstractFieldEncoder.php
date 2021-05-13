<?php


namespace Drupal\io_util\Services\Encoders;


use Drupal\Core\Field\FieldItemList;
use Drupal\field\Entity\FieldConfig;

abstract class AbstractFieldEncoder implements FieldEncoderInterface
{
  public function encodeItems($definition, FieldItemList $values) {
    $returnValue = [
      'type' => $definition->getType(),
      'items' => []
    ];

    foreach($values->getIterator() as $key=>$val) {
      $returnValue['items'][$key] = $this->encodeItem($val);
    }
    return $returnValue;
  }
}

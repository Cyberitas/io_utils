<?php


namespace Drupal\io_util\Services\Encoders;


use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\field\Entity\FieldConfig;

interface FieldEncoderInterface
{
  public function encodeItems($definition, FieldItemList $values);

  /**
   * @param FieldItemInterface $value
   * @return mixed
   */
  public function encodeItem($value);


}

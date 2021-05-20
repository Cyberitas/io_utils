<?php


namespace Drupal\io_utils\Services\Decoders;


use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\Entity\FieldConfig;

interface FieldDecoderInterface
{
  public function decodeItems($encodedValue);

  /**
   * @return mixed
   */
  public function decodeItem($value);

  /**
   * @param $folder string
   * @return mixed
   */
  public function setImportFolder( $folder );

}

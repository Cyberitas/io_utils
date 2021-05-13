<?php


namespace Drupal\io_util\Services\Decoders;


use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\field\Entity\FieldConfig;

abstract class AbstractFieldDecoder implements FieldDecoderInterface
{
  protected $importFolder;

  public function setImportFolder( $folder ) {
    $this->importFolder = $folder;
  }
}

<?php
namespace Drupal\io_utils\Services\Encoders;

use Drupal;
use Drupal\Core\Field\FieldItemList;
use Drupal\io_utils\Services\TaxonomyExport;
use Drupal\io_utils\Services\DrupalExportUtils;

class Entity_reference__block_contentEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  public function encodeItems($definition, FieldItemList $values) {
    $blockEncoding = $this->encodeRawItems($definition, $values);
    $finalEncoding = [];
    $finalEncoding['type'] = $blockEncoding['type'];
    $finalEncoding['items'] = [];
    foreach($blockEncoding['items'] as $item) {
      $newItem = $item;
      $blockEntity = Drupal::entityTypeManager()->getStorage('block_content')->load($newItem['target_id']);
      if($blockEntity) {
        // TODO: This method can be improved since blocks can contain fields, but this treats it very basically.
        // This would be better to walk through multiple fields and handle linked images / etc.
        $newItem['info'] = $blockEntity->get('info')->getValue()[0]['value'];
        $finalEncoding['items'][] = $newItem;
      }
      else {
        echo "Warning: Block Entity could not be found! Target value: ".$newItem['target_id']."\n";
      }
    }
    return $finalEncoding;
  }

  public function encodeRawItems($definition, FieldItemList $values) {
    $returnValue = [
      'type' => 'entity_reference_block_content',
      'items' => []
    ];

    foreach($values->getIterator() as $key=>$val) {
      $returnValue['items'][$key] = $this->encodeItem($val);
    }
    return $returnValue;
  }

  /**
   * @inheritDoc
   */
  public function encodeItem($value)
  {
    return [
      'target_id' => $value->get('target_id')->getValue(),
      'info' => null
    ];
  }
}


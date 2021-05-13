<?php


namespace Drupal\io_util\Services\Decoders;

use Drupal;

class Entity_reference_block_contentDecoder extends AbstractFieldDecoder implements FieldDecoderInterface
{

  public function decodeItems($encodedValue) {
    $items = $encodedValue['items'];
    $decoded = [];

    foreach($items as $item) {
      $decoded[] = $this::decodeItem($item);
    }

    return $decoded;
  }

  /**
   * @inheritDoc
   */
  public function decodeItem($value)
  {
    $info = $value['info'];
    $oldId = $value['target_id'];
    $newId = $oldId;

    $ids = array_keys(Drupal::entityTypeManager()->getStorage('block_content')->loadByProperties(['info' => $info]));
    if(!in_array($newId,$ids)){
      if(count($ids) == 0){
        echo 'No matching content_block was found with info field "'.$info.'", please create one.'."\n";
        return [
          'target_id' => $newId,
        ];
      }
      elseif(count($ids) > 1){
        echo 'Multiple matching content_block options; choosing the first blindly'."\n";
      }
      $newId = $ids[0];
    }

    return [
      'target_id' => $newId,
    ];
  }
}

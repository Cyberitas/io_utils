<?php
namespace Drupal\io_utils\Services\Decoders;

class PathDecoder extends AbstractFieldDecoder implements FieldDecoderInterface
{
  public function decodeItems($encodedValue)
  {
    $finalArray = [];
    if (isset($encodedValue['items']) && is_array($encodedValue['items'])) {
      foreach ($encodedValue['items'] as $item) {
        $finalArray[] = $this->decodeItem($item);
      }
    }
    return $finalArray;
  }

  /**
   * @inheritDoc
   */
  public function decodeItem($value)
  {
    if( isset($value['pid']) ) {
      unset( $value['pid'] );
    }
    return $value;
  }
}

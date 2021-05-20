<?php


namespace Drupal\io_utils\Services\Decoders;


class GenericDecoder extends AbstractFieldDecoder implements FieldDecoderInterface
{

  public function decodeItems($encodedValue) {
    return $encodedValue['items'];
  }

  /**
   * @inheritDoc
   */
  public function decodeItem($value)
  {
    //Not used; full array returned by decodeItems()
    return null;
  }
}

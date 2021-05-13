<?php


namespace Drupal\io_util\Services\Decoders;


use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\DataDefinition;

class BooleanDecoder extends AbstractFieldDecoder implements FieldDecoderInterface
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

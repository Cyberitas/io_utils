<?php


namespace Drupal\io_util\Services\Encoders;



class Entity_referenceEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
    /**
     * @inheritDoc
     */
    public function encodeItem($value)
    {
      return [
        'target_id' => $value->target_id,
      ];
    }
}

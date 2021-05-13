<?php
namespace Drupal\io_util\Services\Encoders;

class Text_with_summaryEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
    /**
     * @inheritDoc
     */
    public function encodeItem($value)
    {
      return [
        'value' => $value->value,
        'summary' => $value->summary,
        'format' => $value->format
      ];
    }
}

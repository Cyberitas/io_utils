<?php

namespace Drupal\io_utils\Services\Encoders;

class Phone_overrideEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
    /**
     * @inheritDoc
     */
    public function encodeItem($value)
    {
      return [
        'sales_override' => $value->get('sales_override')->getValue(),
        'support_override' => $value->get('support_override')->getValue(),
        'override_priority' => $value->get('override_priority')->getValue(),
        'callrail_support' => $value->get('callrail_support')->getValue()
      ];
    }
}

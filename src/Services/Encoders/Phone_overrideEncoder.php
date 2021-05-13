<?php

namespace Drupal\io_util\Services\Encoders;

class Phone_overrideEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
    /**
     * @inheritDoc
     */
    public function encodeItem($value)
    {
      return [
        'sales_override' => $value->sales_override,
        'support_override' => $value->support_override,
        'override_priority' => $value->override_priority,
        'callrail_support' => $value->callrail_support
      ];
    }
}

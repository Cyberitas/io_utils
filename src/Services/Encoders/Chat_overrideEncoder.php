<?php

namespace Drupal\io_util\Services\Encoders;

class Chat_overrideEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
    /**
     * @inheritDoc
     */
    public function encodeItem($value)
    {
      return [
        'chat_department_override' => $value->chat_department_override
      ];
    }
}

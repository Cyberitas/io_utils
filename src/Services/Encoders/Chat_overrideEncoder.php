<?php

namespace Drupal\io_utils\Services\Encoders;

class Chat_overrideEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
    /**
     * @inheritDoc
     */
    public function encodeItem($value)
    {
      return [
        'chat_department_override' => $value->get('chat_department_override')->getValue()
      ];
    }
}

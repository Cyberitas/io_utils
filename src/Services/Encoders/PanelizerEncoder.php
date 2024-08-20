<?php
namespace Drupal\io_utils\Services\Encoders;

class PanelizerEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  /**
   * @inheritDoc
   */
  public function encodeItem($value)
  {
    return [
      'view_mode' => $value->get('view_mode')->getValue(),
      'default' => $value->get('default')->getValue(),
      'panels_display' => $value->get('panels_display')->getValue()
    ];
  }
}

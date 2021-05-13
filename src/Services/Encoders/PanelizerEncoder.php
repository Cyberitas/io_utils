<?php
namespace Drupal\io_util\Services\Encoders;

class PanelizerEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  /**
   * @inheritDoc
   */
  public function encodeItem($value)
  {
    return [
      'view_mode' => $value->view_mode,
      'default' => $value->default,
      'panels_display' => $value->panels_display
    ];
  }
}

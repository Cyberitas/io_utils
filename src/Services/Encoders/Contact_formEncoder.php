<?php
namespace Drupal\io_utils\Services\Encoders;

class Contact_formEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  /**
   * @inheritDoc
   */
  public function encodeItem($value)
  {
    return [
      'contact_type' => $value->get('contact_type')->getValue(),
      'tagline' => $value->get('tagline')->getValue(),
      'sfs_lead_source' => $value->get('sfs_lead_source')->getValue(),
      'sfs_lead_sub_source' => $value->get('sfs_lead_sub_source')->getValue(),
    ];
  }
}

<?php
namespace Drupal\io_util\Services\Encoders;

class Contact_formEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  /**
   * @inheritDoc
   */
  public function encodeItem($value)
  {
    return [
      'contact_type' => $value->contact_type,
      'tagline' => $value->tagline,
      'sfs_lead_source' => $value->sfs_lead_source,
      'sfs_lead_sub_source' => $value->sfs_lead_sub_source,
    ];
  }
}

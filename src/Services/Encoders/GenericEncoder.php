<?php


namespace Drupal\io_util\Services\Encoders;


class GenericEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{

    /**
     * @inheritDoc
     */
    public function encodeItem($value)
    {
      $encodedItem = [];
      $storage = $value->getFieldDefinition()->getFieldStorageDefinition();
      $property_names = $storage->getPropertyNames();

      foreach ($property_names as $property_name) {
        if( $value->isEmpty() ) {
          $encodedItem[$property_name] = null;
        } else if( $property_name == null ) {
          $encodedItem[$property_name] = null;
        } else {
          $encodedItem[$property_name] = $value->get($property_name)->getValue();
        }
      }

      return $encodedItem;
    }
}

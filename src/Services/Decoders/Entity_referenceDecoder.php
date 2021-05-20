<?php


namespace Drupal\io_utils\Services\Decoders;
use Drupal\io_utils\Services\DrupalMediaImporter;

class Entity_referenceDecoder extends AbstractFieldDecoder implements FieldDecoderInterface
{
  public function decodeItems($encodedValue)
  {
    $finalArray = [];
    if( isset($encodedValue['items']) && is_array($encodedValue['items']) ) {
      foreach ($encodedValue['items'] as $item) {
        $finalArray[] = $this->decodeItem($item);
      }
    }
    return $finalArray;
  }

  /**
   * @inheritDoc
   */
  public function decodeItem($value)
  {
    if( isset( $value['referenced_entity'] ) && !empty( $value['referenced_entity'] ) ) {
      if( $value['referenced_entity']['type'] == 'media' ) {
        $mediaImporter = new DrupalMediaImporter();
        $importedMedia = $mediaImporter->importMediaSaveFile( $this->importFolder, $this->importFolder . '/' .  $value['referenced_entity']['export_file'] );
        if( $importedMedia == null ) {
          return null;
        }
        return [
          'target_id' => $importedMedia->id(),
        ];
      } else {
        $type = '(unknown)';
        if( isset($value['referenced_entity']['type']) ) {
          $type = $value['referenced_entity']['type'];
        }
        echo 'No deep entity reference revision support for referenced ' .$type.". Linking to entity ID without deep import.\n";
        unset( $value['referenced_entity'] );
        return $value;
      }
    } else {
      return $value;
    }
  }
}

<?php


namespace Drupal\io_utils\Services\Decoders;


use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\io_utils\Services\DrupalHtmlParser;
use Drupal\io_utils\Services\DrupalMediaExporter;
use Drupal\io_utils\Services\DrupalMediaImporter;

abstract class AbstractTextWithEmbedDecoder extends AbstractFieldDecoder implements FieldDecoderInterface
{

  public function decodeItems($encodedValue) {
    if( isset($encodedValue['embeddedEntities']) && is_array($encodedValue['embeddedEntities']) ) {
      foreach ($encodedValue['embeddedEntities'] as $embeddedEntity ) {
        $this->importEmbeddedEntity( $embeddedEntity );

      }
    }

    return $encodedValue['items'];
  }


  /**
   * @inheritDoc
   */
  public function decodeItem($value)
  {
    //Not used; full array returned by decodeItems()
    return null;
  }

  /**
   * @inheritDoc
   */
  public function importEmbeddedEntity( $embeddedEntity )
  {
    if( isset( $embeddedEntity['type'] ) ) {
      if( $embeddedEntity['type'] == 'media' ) {
        $mediaImporter = new DrupalMediaImporter();
        $mediaImporter->importMediaSaveFile( $this->importFolder, $this->importFolder . '/' .  $embeddedEntity['export_file'] );
      } else {
        echo "\nWARNING: Automatic import of embedded related entity of type " . $embeddedEntity['type'] . " not supported.";
      }
    }
  }
}

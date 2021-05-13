<?php
namespace Drupal\io_util\Services\Decoders;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\io_util\Services\DrupalNodeExporter;
use Drupal\io_util\Services\DrupalParagraphImporter;
use Drupal\facets\Exception\Exception;

class Entity_reference_revisionsDecoder extends AbstractFieldDecoder implements FieldDecoderInterface
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
      if( $value['referenced_entity']['type'] == 'paragraph' ) {
        $paragraphImporter = new DrupalParagraphImporter();
        $importedParagraph = $paragraphImporter->importParagraphSaveFile( $this->importFolder, $this->importFolder . '/' .  $value['referenced_entity']['export_file'] );
        if( $importedParagraph == null ) {
          return null;
        }

        return [
          'target_id' => $importedParagraph->id(),
          'target_revision_id' => $importedParagraph->getRevisionId(),
        ];
      } else {
        echo 'No deep entity reference revision support for referenced ' .$value['referenced_entity']['type'].". Linking to entity ID without deep import.\n";
        unset( $value['referenced_entity'] );
        return $value;
      }
    } else {
      return $value;
    }
  }
}

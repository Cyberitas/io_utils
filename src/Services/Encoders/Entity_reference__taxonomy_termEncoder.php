<?php


namespace Drupal\io_util\Services\Encoders;

//Unlike most encoders, 'author' is not a type. Thus, this is different.

use Drupal;
use Drupal\Core\Field\FieldItemList;
use Drupal\io_util\Services\TaxonomyExport;
use Drupal\io_util\Services\DrupalExportUtils;

class Entity_reference__taxonomy_termEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  public function encodeItems($definition, FieldItemList $values) {
    $taxonomyFolder = DrupalExportUtils::$exportFolder.'/drupal_encoded_taxonomy/';
    $encodingNoSave = $this->encodeRawItems($definition, $values);
    $tidsAndPaths = TaxonomyExport::exportTaxonomy($taxonomyFolder, $encodingNoSave);
    $newEncoding = [];
    $newEncoding['type'] = $encodingNoSave['type'];
    $newItems = [];
    for($i = 0; $i < count($encodingNoSave['items']); $i++) {
      $newItem = $encodingNoSave['items'][$i];
      $tid = $newItem['target_id'];

      if (substr($tidsAndPaths[$tid], 0, strlen(DrupalExportUtils::$exportFolder)) == DrupalExportUtils::$exportFolder) {
        $newItem['path'] = '.' . substr($tidsAndPaths[$tid], strlen(DrupalExportUtils::$exportFolder));
      } else {
        $newItem['path'] = $tidsAndPaths[$tid];
      }

      $newItems[] = $newItem;
    }
    $newEncoding['items'] = $newItems;

    return $newEncoding;
  }

  public function encodeRawItems($definition, FieldItemList $values) {
    $returnValue = [
      'type' => 'entity_reference_taxonomy_term',
      'items' => []
    ];

    foreach($values->getIterator() as $key=>$val) {
      $encoding = $this->encodeItem($val);
      if($encoding) {
        $returnValue['items'][$key] = $encoding;
      }
    }
    return $returnValue;
  }


  /**
   * @inheritDoc
   */
  public function encodeItem($value)
  {
    // echo "Beginning encoding of new taxonomy term...\n";
    $taxonomyEntity = Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($value->target_id);
    if(!$taxonomyEntity){
      echo "Warning: Taxonomy Entity could not be found! Target value: ".$value->target_id."\n";
      return null;
    }
    // echo 'Beginning saving of taxonomy term '.$taxonomyEntity->get('name')->get(0)->getValue()['value']."\n";


    return [
      'name' => $taxonomyEntity->get('name')->get(0)->getValue()['value'],
      'target_id' => $value->target_id,
      'path' => null
    ];
  }
}


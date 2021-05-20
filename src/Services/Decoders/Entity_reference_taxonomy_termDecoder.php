<?php


namespace Drupal\io_utils\Services\Decoders;

use Drupal;
use Drupal\io_utils\Services\TaxonomyAncestryMatchup;

class Entity_reference_taxonomy_termDecoder extends AbstractFieldDecoder implements FieldDecoderInterface
{

  public function decodeItems($encodedValue) {
    $items = $encodedValue['items'];
    $decoded = [];

    foreach($items as $item) {
      $decoded[] = $this::decodeItem($item);
    }

    return $decoded;
  }

  /**
   * @inheritDoc
   */
  public function decodeItem($value)
  {

    // Short circuit decoder if this is a same-site trusted ID
    if( isset($value['skip_validation']) && $value['skip_validation'] == true ) {
      if( isset($value['target_id']) ) {
        return [
          'target_id' => $value['target_id'],
        ];
      }
    }


    // Handle cross-site terms, we can not trust ID nor even name without viewing full term hierarchy
    $name = $value['name'];
    $importPath = $value['path'];

    if( substr($importPath, 0, 2) == './' ) {
      $importPath = $this->importFolder . '/' . substr($importPath, 2);
    }

    // echo 'Decoding taxonomy with import path of '.$importPath."\n";
    if(!file_exists($importPath)) {
      echo "Error importing taxonomy element- file not found."."\n";
    }

    $tmpTerm = json_decode(file_get_contents($importPath), true);
    $vid =$tmpTerm['vid'];
    $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => $vid, 'name' => $name]);
    $passableIds = [];
    if(sizeof($terms) == 0){
      echo 'Error; taxonomy term "'. $name .'" must be created before importing.'."\n";
      return null;
    }
    foreach($terms as $id=>$term) {
      if(TaxonomyAncestryMatchup::checkMatch($id,$importPath)){
        // echo 'Match found!'."\n";
        $passableIds[] = $id;
      }
      else {
        echo 'Name match found, but parental mismatch'."\n";
      }
    }
    if(sizeof($passableIds) == 0){
      echo 'At least one same-named term was found, but they have differing parent names. Please correct this in the future. Picking the first blindly.'."\n";
      $newId = array_keys($terms)[0];
    }
    elseif (sizeof($passableIds) > 1){
      echo 'WARNING! Multiple taxonomu terms match name and ancestry. These may be duplicates. Please correct this in the future. Picking the first blindly.'."\n";
      echo "  All matching terms: ";
      print_r( $passableIds );
      $newId = $passableIds[0];
    }
    else {
      $newId = $passableIds[0];
    }
    return [
      'target_id' => $newId,
    ];
  }

}

<?php


namespace Drupal\io_utils\Services;


use Drupal;

class TaxonomyAncestryMatchup
{
  public static function checkMatch($nativeId, $foreignPath){
    $nativeEntity = Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($nativeId);
    if(!$nativeEntity && !$foreignPath) {
      return true;
    }
    if($nativeEntity xor $foreignPath) {
      return false;
    }
    if($nativeEntity && $foreignPath) {
      $foreignConstructionInfo = json_decode(file_get_contents($foreignPath), true);
      if($foreignConstructionInfo['name'] == $nativeEntity->get('name')->getValue()[0]['value']) {
        return self::checkMatch($nativeEntity->get('parent')->getValue()[0]['target_id'],$foreignConstructionInfo['encoded_parent_path']);
      }
    }
    return false;
  }
}

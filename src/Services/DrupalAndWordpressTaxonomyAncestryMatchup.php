<?php


namespace Drupal\io_util\Services;


use Drupal;

class DrupalAndWordpressTaxonomyAncestryMatchup
{
  public static function checkMatch($nativeId, $foreignId, $foreignCategoryData){
    $nativeEntity = Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($nativeId);
    if(!$nativeEntity && !array_key_exists($foreignId,$foreignCategoryData)) {
      return true;
    }
    if($nativeEntity xor array_key_exists($foreignId,$foreignCategoryData)) {
      return false;
    }
    if($nativeEntity && array_key_exists($foreignId,$foreignCategoryData)) {
      $foreignInfo = $foreignCategoryData[$foreignId];
      if($foreignInfo['name'] == $nativeEntity->get('name')->getValue()[0]['value']) {
        return self::checkMatch($nativeEntity->get('parent')->getValue()[0]['target_id'],$foreignInfo['parent'],$foreignCategoryData);
      }
    }
    return false;
  }
}

<?php


namespace Drupal\io_utils\Services;


use Drupal;

class TaxonomyExport
{

  protected static function safeGet($taxonomyEntity, $key) {
    return $taxonomyEntity->get($key)->get(0) ? $taxonomyEntity->get($key)->get(0)->getValue()['value'] : null;
  }

  public static function getConstructionInfo($taxonomyEntity){
    $constructionInfo = [
      'tid' => $taxonomyEntity->get('tid')->get(0)->getValue()['value'],
      'uuid' => $taxonomyEntity->get('uuid')->get(0)->getValue()['value'],
      'revision_id' => $taxonomyEntity->get('revision_id')->get(0)->getValue()['value'],
      'langcode' => TaxonomyExport::safeGet($taxonomyEntity, 'langcode'),
      'vid' => $taxonomyEntity->get('vid')->get(0) ? $taxonomyEntity->get('vid')->get(0)->getValue()['target_id'] : null,
      'revision_created' => TaxonomyExport::safeGet($taxonomyEntity, 'revision_created'),
      'revision_user' => TaxonomyExport::safeGet($taxonomyEntity, 'revision_user'),
      'revision_log_message' => TaxonomyExport::safeGet($taxonomyEntity, 'revision_log_message'),
      'status' => TaxonomyExport::safeGet($taxonomyEntity, 'status'),
      'name' => TaxonomyExport::safeGet($taxonomyEntity, 'name'),
      'description' => TaxonomyExport::safeGet($taxonomyEntity, 'description'),
      'weight' => TaxonomyExport::safeGet($taxonomyEntity, 'weight'),
      'parent' => $taxonomyEntity->get('parent')->get(0) ? $taxonomyEntity->get('parent')->get(0)->getValue()['target_id'] : null,
      'changed' => TaxonomyExport::safeGet($taxonomyEntity, 'changed'),
      'default_langcode' => TaxonomyExport::safeGet($taxonomyEntity, 'default_langcode'),
      'revision_default' => TaxonomyExport::safeGet($taxonomyEntity, 'revision_default'),
      'revision_translation_affected' => TaxonomyExport::safeGet($taxonomyEntity, 'revision_translation_affected'),
      'metatag' => TaxonomyExport::safeGet($taxonomyEntity, 'metatag'),
      'path' => $taxonomyEntity->get('path')->get(0) ? [
        'alias' => $taxonomyEntity->get('path')->get(0)->getValue()['alias'],
        'pid' => $taxonomyEntity->get('path')->get(0)->getValue()['pid'],
        'langcode' => $taxonomyEntity->get('path')->get(0)->getValue()['langcode']
      ] : null,
      'parent_name' => null,
      'encoded_parent_path' => null
    ];
    return $constructionInfo;
  }


  public static function exportTaxonomy($taxonomyFolder, $encoding) {
    if(!file_exists($taxonomyFolder)){
      mkdir($taxonomyFolder, 0777, true);
    }
    $files = [];
    foreach($encoding['items'] as $value) {
      // echo 'Saving Taxonomy at id '.$value['target_id']."\n";
      $taxonomyEntity = Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($value['target_id']);
      if(!$taxonomyEntity){
        echo 'Taxonomy at id '.$value['target_id'].' could not be found- terminating program.';
        exit(1);
      }
      $constructionInfo = TaxonomyExport::getConstructionInfo($taxonomyEntity);
      $tid = $constructionInfo['tid'];
      $parent = $constructionInfo['parent'];
      $basename = 'Taxonomy'.$tid.'.json';
      $saveName = $taxonomyFolder.$basename;
      $files[$tid] = $saveName;
      $parentEntity = Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($parent);
      if($parentEntity) {
        $constructionInfo['encoded_parent_path'] = $taxonomyFolder.'Taxonomy'.$parent.'.json';
        $constructionInfo['parent_name'] = $parentEntity->get('name')->getValue()[0]['value'];
      }

      if(!file_exists($saveName)) {
        $content = json_encode($constructionInfo);
        file_put_contents($saveName, $content);
        // echo 'Saved Taxonomy at id ' . $value['target_id'] . "!\n";
        echo ' Exported TID ' . $value['target_id'] . '...';
      }
      else {
        // echo 'Taxonomy at id '.$value['target_id']." already saved.\n";
      }


      while($parentEntity){
        $parentConstructionInfo = TaxonomyExport::getConstructionInfo($parentEntity);
        $parentTid = $parentConstructionInfo['tid'];
        $parentBasename = 'Taxonomy'.$parentTid.'.json';
        $parentSaveName = $taxonomyFolder.$parentBasename;
        $files[$parentTid] = $parentSaveName;
        //parentEntity is not used again in the loop- this is the iterator
        $parentEntity = Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($parentConstructionInfo['parent']);
        if($parentEntity) {
          $parentConstructionInfo['encoded_parent_path'] = $taxonomyFolder.'Taxonomy'.$parentConstructionInfo['parent'].'.json';
        }

        if(file_exists($parentSaveName)){
          echo 'Taxonomy at id '.$parentTid." already saved.\n";
          break;
        }
        else {
          $parentContent = json_encode($parentConstructionInfo);
          file_put_contents($parentSaveName,$parentContent);
          echo 'Saved Parent Taxonomy at id '.$parentTid."!\n";
        }
        $parent = $parentConstructionInfo['parent'];
        $parentEntity = Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($parent);
      }
    }
    return $files;
  }

}

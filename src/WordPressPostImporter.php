<?php


namespace Drupal\io_util\Services;

use Drupal;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\io_util\Services\Decoders\FieldDecoderInterface;
use Drupal\io_util\Services\Decoders\GenericDecoder;
use Drupal\io_util\Services\Encoders\FieldEncoderInterface;
use Drupal\io_util\Services\Encoders\GenericEncoder;
use Drupal\node\Entity\Node;

class WordPressPostImporter
{
  public static function importSaveFile($filename)
  {
    if (!file_exists($filename)) {
      echo "Post with that filename has not yet been exported";
      return;
    }
    $serialized = file_get_contents($filename);
    $saveFormat = new ContentProcessors\ContentItem();
    $saveFormat->unserialize($serialized);

    $nodeTitle = $saveFormat->getTitle();
    $nodeBody = [
          'value' => $saveFormat->getContent(),
          'summary' => '',
          'format' => 'rich_text'
    ];
    if($saveFormat->getPostStatus() == 'publish') {
      $nodePostStatus = true;
    }
    else {
      $nodePostStatus = false;
    }
    //commentStatus unused
    //pingStatus unused
    $nodePostType = 'business_resource_article';
    $categories = $saveFormat->getCategories();
    $categoryItems = [];
    foreach($categories as $key=>$value){
      $categoryName = $value['name'];
      $categories = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $categoryName]);
      $passedCategories = [];
      foreach($categories as $category){
        if(DrupalAndWordpressTaxonomyAncestryMatchup::checkMatch($category->id(),$key,$categories)){
          $passedCategories[] = $category;
        }
      }
      if(sizeof($passedCategories) == 0) {
        echo 'Error- no matching business resource categories with the name '.$categoryName.", this tag can't be imported\n";
        $tagId = 0;
      }
      elseif(sizeof($passedCategories) > 1) {
        echo 'Warning- multiple categories with the name '.$categoryName.", assigning one blindly...\n";
        $tagId = $passedCategories[0]->get('tid')->getValue()[0]['value'];
      }
      else {
        $tagId = $passedCategories[0]->get('tid')->getValue()[0]['value'];
      }
      $categoryItem = [
        'target_id' => $tagId
      ];

      $categoryItems[] = $categoryItem;
    }
    //url unused
    //tagString unused-worked around with new field advancedTagArray, which works better across platforms.
    $tags = $saveFormat->getAdvancedTagArray();
    $tagItems = [];
    foreach($tags as $tag){
      $tagName = $tag['name'];
      $terms = Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $tagName]);
      $passedTerms = [];
      foreach($terms as $term){
        if($term->get('parent')->getValue()[0]['target_id'] == 0){
          $passedTerms[] = $term;
        }
      }
      if(sizeof($passedTerms) == 0) {
        echo 'Error- no matching business resource tags with the name '.$tagName.", this tag can't be imported\n";
        $tagId = 0;
      }
      elseif(sizeof($passedTerms) > 1) {
        echo 'Warning- multiple terms with the name '.$tagName.", assigning one blindly...\n";
        $tagId = $passedTerms[0]->get('tid')->getValue()[0]['value'];
      }
      else {
        $tagId = $passedTerms[0]->get('tid')->getValue()[0]['value'];
      }
      $tagItem = [
        'target_id' => $tagId
      ];

      $tagItems[] = $tagItem;
    }










    $node = Node::create([
      'type' => $nodePostType,
      'title' => $nodeTitle,
    ]);
    $node->save();


    $author = user_load_by_name($saveFormat->getAuthor()['display_name']);
    if($author) {
      $authorId = $author->id();
      $node->set('uid', $authorId);
    }
    $node->set('body',$nodeBody);
    $node->set('promote', false);
    $node->set('status', $nodePostStatus);
    if($saveFormat->getPostStatus() == 'publish') {
      $node->set('moderation_state', 'published');
    }
    $node->set('field_business_resource_category', $categoryItems);
    $node->set('field_business_resource_tags', $tagItems);




    echo $node->id();
  }
}

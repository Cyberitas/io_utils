<?php


namespace Drupal\io_util\Services;


use Drupal;
use Drupal\io_util\Services\Encoders\AuthorEncoder;
use Drupal\node\Entity\Node;

class DrupalNodeExporter
{
  /**
   * @param $nodeId
   * @param $filename
   * @return array
   * @throws Drupal\Core\Entity\EntityMalformedException
   */
  public function generateSaveFile($nodeId, $filename): array
  {
    DrupalExportUtils::$exportFolder = dirname( $filename );

    echo "\nExporting node: ".$nodeId."...";
    DrupalExportUtils::$warnings = [];
    $node = Node::load($nodeId);
    if($node) {

      if( !$node->isPublished() ) {
        echo 'NOT PUBLISHED - SKIPPING ';
        echo $node->toUrl()->toString();
        return [];
      }

      $saveFormat = new ContentProcessors\ContentItem();
      $saveFormat->setFormat('drupal');
      $saveFormat->setId($nodeId); //May require mapping for WordPress integration

      $postDate = date( 'Y-m-d H:i:s', $node->getCreatedTime());
      $saveFormat->setDate( $postDate );

      $saveFormat->setTitle($node->getTitle());

      $definitions = Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node->bundle());

      $authorEncoder = new AuthorEncoder();
      $saveFormat->setAuthor($authorEncoder->encodeItems($definitions['uid'], $node->get('uid'))); //May require mapping for WordPress integration

      $saveFormat->setPostStatus($node->isPublished()); //May require mapping for WordPress integration
      $saveFormat->setCommentStatus(null);
      $saveFormat->setPingStatus(null);
      $saveFormat->setPostType($node->getType()); //May require mapping for WordPress integration

      $saveFormat->setCategories(null);
      $saveFormat->setTagString("");

      $attachedData = array();
      foreach($node->getFields() as $key=>$values) {
        $definition = $definitions[$key];
        if($definition->getTargetBundle()) {
          $attachedData[$key] = DrupalExportUtils::encodeField($definition, $node, $key, $values);
        }
      }
      if( $node->get('path') && !$node->get('path')->isEmpty() ) {
        $attachedData['path'] = DrupalExportUtils::encodeField($definitions['path'], $node, 'path', $node->get('path'));
      }

      $saveFormat->setAttachedData($attachedData);
      $saveFormat->setUrl($node->toUrl()->toString());
      file_put_contents($filename, $saveFormat->serialize());
    }
    else {
      echo "N/A no node with that ID";
      DrupalExportUtils::$warnings[] = "N/A no node with that ID";
    }
    return DrupalExportUtils::$warnings;
  }
}

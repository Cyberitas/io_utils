<?php


namespace Drupal\io_util\Services;
use Drupal;


class DrupalParagraphExporter
{

  /**
   * @param Drupal\paragraphs\Entity\Paragraph|null $paragraph
   * @return string
   * @throws Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws Drupal\Core\Entity\EntityMalformedException
   */
  public function generateSaveFile($paragraph)
  {
    $filename = null;
    if( !file_exists(DrupalExportUtils::$exportFolder . '/attached_paragraphs') ) {
      mkdir( DrupalExportUtils::$exportFolder . '/attached_paragraphs' );
    }
    if($paragraph) {
      $filename = 'attached_paragraphs/paragraph-'.$paragraph->id().'.json';
      $fullPath = DrupalExportUtils::$exportFolder . '/' . $filename;
      echo "\n    Exporting paragraph: ".$paragraph->id()."...";
      $saveFormat = new ContentProcessors\ContentItem();
      $saveFormat->setFormat('drupal-paragraph');
      $saveFormat->setId($paragraph->id()); //May require mapping for WordPress integration
      $postDate = date( 'Y-m-d H:i:s', $paragraph->getCreatedTime());
      $saveFormat->setDate( $postDate );
      $saveFormat->setPostType($paragraph->getType());

      $definitions = Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', $paragraph->bundle());

      $attachedData = array();

      foreach($paragraph->getFields() as $key=>$values) {
        $definition = $definitions[$key];

        if($definition->getTargetBundle()) {
          $attachedData[$key] = DrupalExportUtils::encodeField($definition, $paragraph, $key, $values);
        }
      }
      $saveFormat->setAttachedData($attachedData);
      file_put_contents($fullPath, $saveFormat->serialize());
    }
    return $filename;
  }


}

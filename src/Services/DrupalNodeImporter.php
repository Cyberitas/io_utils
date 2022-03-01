<?php


namespace Drupal\io_utils\Services;


use Drupal;
use Drupal\Core\File\FileSystemInterface;
use Drupal\io_utils\Services\Decoders\FieldDecoderInterface;
use Drupal\io_utils\Services\Decoders\GenericDecoder;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;

class DrupalNodeImporter
{

  // This media upload is already "perfect" - we trust it... but it may be duplicate
  public function importInlineMediaItem($mediaItem, $importFolder) {

    if( !isset($mediaItem['path']) || empty($mediaItem['path']) ) {
      return;
    }
    $mediaItem['target_id'] = 0;

    $decoder = new Drupal\io_utils\Services\Decoders\ImageDecoder();
    $decoder->setImportFolder( $importFolder );
    $decoder->setAllowDuplicateImages(false);
    $retVal = $decoder->decodeItem($mediaItem);
    $newId = null;
    if( isset($retVal['target_id']) ) $newId = $retVal['target_id'];

    if( $newId == null ) {
      echo "Inline not imported";
    } else {
      echo "Inline image imported to file id $newId";
    }
  }

  public function importSaveFile($filename)
  {
    if (!file_exists($filename)) {
      echo "Post with that filename has not yet been exported";
      return [ 'new' => null, 'old' => null ];
    }
    $importFolder = dirname($filename);
    $serialized = file_get_contents($filename);
    $saveFormat = new ContentProcessors\ContentItem();
    $saveFormat->unserialize($serialized);

    // Import dependent media
    if( $saveFormat->getInlineMedia() && is_array( $saveFormat->getInlineMedia() ) && count( $saveFormat->getInlineMedia() ) > 0 ) {
      foreach( $saveFormat->getInlineMedia() as $mediaItem ) {
        $this->importInlineMediaItem( $mediaItem, $importFolder );
      }
    }

    /** @var Node $node */
    $node = Node::create([
      'type' => $saveFormat->getPostType(),
      'title' => $saveFormat->getTitle(),
    ]);
    $authorName = $saveFormat->getAuthor()['items'][0]['name'];
    $author = user_load_by_name($authorName);
    if($author) {
      $authorId = $author->id();
      $node->set('uid', $authorId);
    }
    if( $saveFormat->getDate() !== null ) {
      $postDate = \DateTime::createFromFormat('Y-m-d H:i:s', $saveFormat->getDate() );
      if( $postDate ) {
        $node->setCreatedTime( $postDate->format('U') );
      }
    }

    $node->setPublished($saveFormat->getPostStatus());

    foreach ($node->getFields() as $key => $values) {
      $definition = Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node->bundle())[$key];
      if ($definition->getTargetBundle()) {
        $realType = $definition->getType();
        $encodedValue = null;
        $savedType = $realType;
        switch ($key) {
          case 'path':
            //skip
            break;
          case 'body':
          case 'field_thewire_body':
            if (isset($saveFormat->getAttachedData()[$key])) {
              $encodedValue = $saveFormat->getAttachedData()[$key];
            } else {
              $encodedValue = $saveFormat->getContent();
            }
            break;
          default:
            if (isset($saveFormat->getAttachedData()[$key])) {
              $encodedValue = $saveFormat->getAttachedData()[$key];
              $savedType = $encodedValue['type'];
            }
            break;
        }

        $typeMismatch = false;
        if($realType != $savedType) {
          $typeMismatch = true;
        }

        if($typeMismatch && (class_exists('Drupal\\io_utils\\Services\\Decoders\\' . ucfirst($savedType) . 'Decoder'))){
          $decoderClass = 'Drupal\\io_utils\\Services\\Decoders\\' . ucfirst($savedType) . 'Decoder';
          if (class_exists($decoderClass)) {
            /** @var FieldDecoderInterface $decoder */
            $decoder = new $decoderClass;
          } else {
            echo 'Warning, no decoder: ' . $realType . "\n";
            /** @var FieldDecoderInterface $decoder */
            $decoder = new GenericDecoder();
          }
        }
        else {
          $decoderClass = 'Drupal\\io_utils\\Services\\Decoders\\' . ucfirst($realType) . 'Decoder';
          if (class_exists($decoderClass)) {
            /** @var FieldDecoderInterface $decoder */
            $decoder = new $decoderClass;
          } else {
            echo 'Warning, no decoder: ' . $realType . "\n";
            /** @var FieldDecoderInterface $decoder */
            $decoder = new GenericDecoder();
          }
        }
        $decoder->setImportFolder( $importFolder );

        $node->set($key, $decoder->decodeItems($encodedValue));
      }
    }

    // Path
    if (isset($saveFormat->getAttachedData()['path']['items'][0]['alias']) &&
      isset($saveFormat->getAttachedData()['path']['items'][0]['langcode']) ) {
      $alias = $saveFormat->getAttachedData()['path']['items'][0]['alias'];
      $langcode = $saveFormat->getAttachedData()['path']['items'][0]['langcode'];
      if( \Drupal::service('path_alias.repository')->lookUpByAlias($alias, $langcode) !== null ) {
        echo "Warning, URL alias $alias already exists, not setting path.\n";
      } else {
        $encodedValue = $saveFormat->getAttachedData()['path'];
        $decoder = new Drupal\io_utils\Services\Decoders\PathDecoder();
        $decoder->setImportFolder($importFolder);
        $node->set('path', $decoder->decodeItems($encodedValue));
      }
    }

    // Tags
    if($saveFormat->getAdvancedTagArray() != null) {
      foreach ($saveFormat->getAdvancedTagArray() as $advancedTag) {
        if($advancedTag != null && is_array($advancedTag) && isset($advancedTag['name'])) {
          //echo "Tag: " . $advancedTag['name'] . "\n";
          $thewire_blog_tag = $this->getTag($advancedTag['name']);
          if($thewire_blog_tag != null) {
            $this->associateTagWithNode($node, $thewire_blog_tag);
          }
        }
      }
      //var_dump($node->field_thewire_tags->getValue());
    }

    $node->save();
    echo "\nNew node created at NID " . $node->id() . " with URL " . $node->toUrl()->toString();
    return [ 'new' => $node->toUrl()->toString(), 'old' => $saveFormat->getUrl() ];
  }

  /**
   * Retrieve the wire blog tag.
   * Create it if it does not exist.
   *
   * @param string $tagName
   * @return EntityBase|EntityInterface
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   * @return Term
   */
  private function getTag($tagName) {
    $vid  ="thewire_blog_tags";

    // Load all the terms in the thewire_blog_tags taxonomy.
    $tree = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree(
      $vid,
      0,
      1,
      TRUE
    );

    $selectedTerm = null;
    if($tree) {
      foreach ($tree as $term) {
         if($term->getName() == $tagName) {
           //echo "Term already exists: [$vid][$tagName]\n";
           $selectedTerm = $term;
           break;
         }
      }
    }

    if($selectedTerm == null) {
      // Create a new term.
      //echo " Creating a new tag: [$vid][$tagName]...";
      $selectedTerm = \Drupal\taxonomy\Entity\Term::create([
        'vid' => $vid,
        'name' => $tagName,
      ]);
      $selectedTerm->save();
    }

    return $selectedTerm;
  }

  /**
   * Associate a tag with node.
   *
   * @param Node $node
   * @param Term $tag
   */
  private function associateTagWithNode(&$node, $tag)
  {
    //echo "Associate tag with node: " . $tag->label() . "\n";
    $tagAssociated = false;
    if($tag != null) {
      $tagsInNode = $node->field_thewire_tags->getValue();
      if ($tagsInNode) {
        foreach ($tagsInNode as $key => $tagInNode) {
          if($tagInNode[$key]['target_id'] == $tag->id()) {
            $tagAssociated = true;
          }
        }
      }

      if(!$tagAssociated) {
        // Associate tag with node.
        $node->field_thewire_tags->appendItem(['target_id' => $tag->id()]);
      }
    }
  }
}

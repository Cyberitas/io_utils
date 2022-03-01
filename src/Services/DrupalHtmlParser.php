<?php

namespace Drupal\io_utils\Services;

class DrupalHtmlParser
{
  /**
   * @var \DOMDocument $html
   */
  private $dom;

  public function setHtml($html) {
    $this->dom = new \DOMDocument();

    // Avoid errors with custom tags
    // https://stackoverflow.com/questions/1148928/disable-warnings-when-loading-non-well-formed-html-by-domdocument-php/17559716#17559716
    $libxml_previous_state = libxml_use_internal_errors(true);
    $this->dom->loadHTML( $html );
    libxml_clear_errors();
    libxml_use_internal_errors($libxml_previous_state);
  }

  /**
   * @param bool $requireUuid Only return tags with the entity-uuid attribute set
   * @param string $limitToType The entity type to return or null for all
   * @return array all attributes (key=>value) from the drupal-embed tag
   */
  public function getEmbeddedEntityTagAttribs( $requireUuid = true, $limitToType = null ) {
    $results = [];
    if( $this->dom ) {
      $this->parseTags( $results, 'drupal-entity', $requireUuid, $limitToType );
      $this->parseTags( $results, 'drupal-media', $requireUuid, ($limitToType=='media')?null:$limitToType );
    }
    return $results;
  }

  private function parseTags( &$results, $tagName, $requireUuid, $limitToType ) {
    $items = $this->dom->getElementsByTagName($tagName);
    if(count($items) > 0) {
      foreach ($items as $mediaEmbed) {
        $entityType = $mediaEmbed->getAttribute('data-entity-type');
        $entityUuid = $mediaEmbed->getAttribute('data-entity-uuid');
        if( ( $limitToType == null || $entityType == $limitToType ) &&
          ( !$requireUuid || !empty($entityUuid) ) ) {
          $results[$entityUuid] = [];
          foreach ($mediaEmbed->attributes as $attr) {
            $results[$entityUuid][$attr->name] = $attr->value;
          }
        }
      }
    }
  }
}

<?php

namespace Drupal\io_util\Services\ContentProcessors;

use Serializable;

class ContentItem implements Serializable
{
  /**
   * @var mixed
   */
  private $format;
  /**
   * @var mixed
   */
  private $id;
  /**
   * @var string
   */
  private $title;
  /**
   * @var  mixed
   */
  private $author;
  /**
   * @var string
   */
  private $content;
  /**
   * @var string
   */
  private $postStatus;
  /**
   * @var string
   */
  private $commentStatus;
  /**
   * @var string
   */
  private $pingStatus;
  /**
   * @var string
   */
  private $postType;
  /**
   * @var array
   */
  private $categories;
  /**
   * @var array
   */
  private $attachedData;
  /**
   * @var string
   */
  private $url;
  /**
   * @var string
   */
  private $tagString;
  /**
   * @var array
   */
  private $advancedTagArray;
  /**
   * @var array
   */
  private $inlineMedia;

  /**
   * @return string
   */
  public function getDate(): string {
    return $this->date;
  }

  /**
   * @param string $date
   */
  public function setDate( string $date ): void {
    $this->date = $date;
  }


  /**
   * @var string
   */
  private $date;

  /**
   * @return mixed
   */
  public function getFormat(): string
  {
    return $this->format;
  }

  /**
   * @param mixed $format
   */
  public function setFormat($format): void
  {
    $this->format = $format;
  }

  /**
   * @return mixed
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id)
  {
    $this->id = $id;
  }

  /**
   * @return string
   */
  public function getTitle()
  {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle($title)
  {
    $this->title = $title;
  }

  /**
   * @return mixed
   */
  public function getAuthor()
  {
    return $this->author;
  }

  /**
   * @param mixed $author
   */
  public function setAuthor($author)
  {
    $this->author = $author;
  }

  /**
   * @return string
   */
  public function getContent()
  {
    return $this->content;
  }

  /**
   * @param string $content
   */
  public function setContent($content)
  {
    $this->content = $content;
  }

  /**
   * @return string
   */
  public function getPostStatus()
  {
    return $this->postStatus;
  }

  /**
   * @param string $postStatus
   */
  public function setPostStatus($postStatus)
  {
    $this->postStatus = $postStatus;
  }

  /**
   * @return string
   */
  public function getCommentStatus()
  {
    return $this->commentStatus;
  }

  /**
   * @param string $commentStatus
   */
  public function setCommentStatus($commentStatus)
  {
    $this->commentStatus = $commentStatus;
  }

  /**
   * @return string
   */
  public function getPingStatus()
  {
    return $this->pingStatus;
  }

  /**
   * @param string $pingStatus
   */
  public function setPingStatus($pingStatus)
  {
    $this->pingStatus = $pingStatus;
  }

  /**
   * @return string
   */
  public function getPostType()
  {
    return $this->postType;
  }

  /**
   * @param string $postType
   */
  public function setPostType($postType)
  {
    $this->postType = $postType;
  }

  /**
   * @return array
   */
  public function getCategories()
  {
    return $this->categories;
  }

  /**
   * @param array $categories
   */
  public function setCategories($categories)
  {
    $this->categories = $categories;
  }

  /**
   * @return array
   */
  public function getAttachedData()
  {
    return $this->attachedData;
  }

  /**
   * @param array $attachedData
   */
  public function setAttachedData($attachedData)
  {
    $this->attachedData = $attachedData;
  }

  /**
   * @return string
   */
  public function getUrl(): string
  {
    return $this->url;
  }

  /**
   * @param string $url
   */
  public function setUrl($url): void
  {
    $this->url = $url;
  }

  /**
   * @return string
   */
  public function getTagString(): string
  {
    return $this->tagString;
  }

  /**
   * @param string $tagString
   */
  public function setTagString($tagString): void
  {
    $this->tagString = $tagString;
  }

  /**
   * @return array
   */
  public function getAdvancedTagArray()
  {
    return $this->advancedTagArray;
  }

  /**
   * @param array|null $advancedTagArray
   */
  public function setAdvancedTagArray($advancedTagArray): void
  {
    $this->advancedTagArray = $advancedTagArray;
  }

  /**
   * @return array|null
   */
  public function getInlineMedia()
  {
    return $this->inlineMedia;
  }

  /**
   * @param array|null $inlineMedia
   */
  public function setInlineMedia($inlineMedia): void
  {
    $this->inlineMedia = $inlineMedia;
  }


  public function serialize()
  {
    return json_encode([
      'format' => $this->format,
      'id' => $this->id,
      'date' => $this->date,
      'title' => $this->title,
      'author' => $this->author,
      'content' => $this->content,
      'postStatus' => $this->postStatus,
      'commentStatus' => $this->commentStatus,
      'pingStatus' => $this->pingStatus,
      'postType' => $this->postType,
      'categories' => $this->categories,
      'attachedData' => $this->attachedData,
      'url' => $this->url,
      'tagString' => $this->tagString,
      'advancedTagArray' => $this->advancedTagArray,
      'inlineMedia' => $this->inlineMedia
    ], JSON_PRETTY_PRINT );
  }

  public function unserialize($serialized)
  {
    $obj = json_decode($serialized, true);
    $this->setFormat(isset($obj['format']) ? $obj['format'] : null );
    $this->setId(isset($obj['id']) ? $obj['id'] : null );
    $this->setDate(isset($obj['date']) ? $obj['date'] : null );
    $this->setTitle(isset($obj['title']) ? $obj['title'] : null );
    $this->setAuthor(isset($obj['author']) ? $obj['author'] : null );
    $this->setContent(isset($obj['content']) ? $obj['content'] : null );
    $this->setPostStatus(isset($obj['postStatus']) ? $obj['postStatus'] : null );
    $this->setCommentStatus(isset($obj['commentStatus']) ? $obj['commentStatus'] : null );
    $this->setPingStatus(isset($obj['pingStatus']) ? $obj['pingStatus'] : null );
    $this->setPostType(isset($obj['postType']) ? $obj['postType'] : null );
    $this->setCategories(isset($obj['categories']) ? $obj['categories'] : null );
    $this->setAttachedData(isset($obj['attachedData']) ? $obj['attachedData'] : null );
    $this->setUrl(isset($obj['url']) ? $obj['url'] : null );
    $this->setTagString(isset($obj['tagString']) ? $obj['tagString'] : null );
    $this->setAdvancedTagArray(isset($obj['advancedTagArray']) ? $obj['advancedTagArray'] : null );
    $this->setInlineMedia(isset($obj['inlineMedia']) ? $obj['inlineMedia'] : null );
  }
}

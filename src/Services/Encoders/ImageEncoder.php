<?php


namespace Drupal\io_utils\Services\Encoders;


use Drupal\Core\Field\FieldItemList;
use Drupal\io_utils\Services\DrupalExportUtils;
use Drupal\io_utils\Services\DrupalFileExporter;

class ImageEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{
  public function encodeItems($definition, FieldItemList $values)
  {
    $mediaFolder = DrupalExportUtils::$exportFolder . '/attached_files/';
    $urisAndPaths = DrupalFileExporter::exportFile($mediaFolder, $values);
    $encodingNoImage = $this->encodeRawItems($definition, $values);
    $newEncoding = [];
    $newEncoding['type'] = $encodingNoImage['type'];
    $newItems = [];

    for ($i = 0; $i < count($encodingNoImage['items']); $i++) {
      $newItem = $encodingNoImage['items'][$i];
      $newItem['uri'] = $urisAndPaths[$i]['uri'];
      if (substr($urisAndPaths[$i]['path'], 0, strlen(DrupalExportUtils::$exportFolder)) == DrupalExportUtils::$exportFolder) {
        $newItem['path'] = '.' . substr($urisAndPaths[$i]['path'], strlen(DrupalExportUtils::$exportFolder));
      } else {
        $newItem['path'] = $urisAndPaths[$i]['path'];
      }
      $newItems[] = $newItem;
    }
    $newEncoding['items'] = $newItems;
    return $newEncoding;
  }

  public function encodeRawItems($definition, FieldItemList $values)
  {
    $returnValue = [
      'type' => $definition->getType(),
      'items' => []
    ];

    foreach ($values->getIterator() as $key => $val) {
      $returnValue['items'][$key] = $this->encodeItem($val);
    }

    return $returnValue;
  }

  /**
   * @inheritDoc
   */
  public function encodeItem($value)
  {
    return [
      'target_id' => $value->get('target_id')->getValue(),
      'alt' => $value->get('alt')->getValue(),
      'title' => $value->get('title')->getValue(),
      'width' => $value->get('width')->getValue(),
      'height' => $value->get('height')->getValue(),
      'uri' => null,
      'path' => null
    ];
    //URI and image path are filled in by a separate system
  }
}

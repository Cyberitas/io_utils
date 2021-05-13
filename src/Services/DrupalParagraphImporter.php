<?php
namespace Drupal\io_util\Services;

use Drupal;
use Drupal\io_util\Services\Decoders\FieldDecoderInterface;
use Drupal\io_util\Services\Decoders\GenericDecoder;

class DrupalParagraphImporter
{

  /**
   * @param $filename
   * @return Drupal\paragraphs\Entity\Paragraph|null
   * @throws Drupal\Core\Entity\EntityStorageException
   */
  public function importParagraphSaveFile($baseDir, $filename)
  {
    if (!file_exists($filename)) {
      echo "Paragraph definition not found at " . $filename . ", import failed.\n";
      return null;
    }

    /** @var DrupalNodeImporter $nodeImporter */
    $nodeImporter = Drupal::service('io_util.node_importer');

    // $importFolder = dirname($filename);
    $serialized = file_get_contents($filename);
    $saveFormat = new ContentProcessors\ContentItem();
    $saveFormat->unserialize($serialized);

    // Import dependent media
    if( $saveFormat->getInlineMedia() && is_array( $saveFormat->getInlineMedia() ) && count( $saveFormat->getInlineMedia() ) > 0 ) {
      foreach( $saveFormat->getInlineMedia() as $mediaItem ) {
        $nodeImporter->importInlineMediaItem( $mediaItem, $baseDir );
      }
    }

    /** @var Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = Drupal\paragraphs\Entity\Paragraph::create([
      'type' => $saveFormat->getPostType(),
      //'title' => $saveFormat->getTitle(),
    ]);

    foreach ($paragraph->getFields() as $key => $values) {
      $definition = Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', $paragraph->bundle())[$key];
      if ($definition->getTargetBundle()) {
        $realType = $definition->getType();

        $encodedValue = null;
        $savedType = $realType;
        if (isset($saveFormat->getAttachedData()[$key])) {
          $encodedValue = $saveFormat->getAttachedData()[$key];
          $savedType = $encodedValue['type'];
        }
        $typeMismatch = false;
        if($realType != $savedType) {
          $typeMismatch = true;
        }

        if($typeMismatch && (class_exists('Drupal\\io_util\\Services\\Decoders\\' . ucfirst($savedType) . 'Decoder'))){
          $decoderClass = 'Drupal\\io_util\\Services\\Decoders\\' . ucfirst($savedType) . 'Decoder';
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
          $decoderClass = 'Drupal\\io_util\\Services\\Decoders\\' . ucfirst($realType) . 'Decoder';
          if (class_exists($decoderClass)) {
            /** @var FieldDecoderInterface $decoder */
            $decoder = new $decoderClass;
          } else {
            echo 'Warning, no decoder: ' . $realType . "\n";
            /** @var FieldDecoderInterface $decoder */
            $decoder = new GenericDecoder();
          }
        }
        $decoder->setImportFolder( $baseDir );

        $paragraph->set($key, $decoder->decodeItems($encodedValue));
      }
    }

    $paragraph->isNew();
    $paragraph->save();
    // echo "    New paragraph created at ID " . $paragraph->id();
    return $paragraph;
  }

}

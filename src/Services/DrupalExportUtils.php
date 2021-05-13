<?php

namespace Drupal\io_util\Services;
use Drupal;
use Drupal\io_util\Services\Encoders\FieldEncoderInterface;
use Drupal\io_util\Services\Encoders\GenericEncoder;

class DrupalExportUtils
{
  /**
   * @var String[]
   */
  public static $warnings = [];

  /** @var string */
  public static $exportFolder = '.';

  public static function encodeField($definition, $entity, $key, $values)
  {
    $retVal = null;

    // Ideally this section should be moved to a shared service method so cyclic/recursive types like paragraphs
    // and entity references are better handled.
    $type = $definition->getType();
    $encoderClass = 'Drupal\\io_util\\Services\\Encoders\\'.ucfirst($type).'Encoder';
    if(class_exists($encoderClass)) {
      /** @var FieldEncoderInterface $encoder */
      $encoder = new $encoderClass;
    }
    else {
      echo 'Warning: Encoder for data of type '.$type.' for field '.$key.' was not found'."\n";
      self::$warnings[] = 'Warning: Encoder for data of type '.$type.' for field '.$key.' was not found'."\n";
      /** @var FieldEncoderInterface $encoder */
      $encoder = new GenericEncoder();
    }

    if($type == 'entity_reference' || $type == 'entity_reference_revisions' ) {
      $targetType = $entity->get($key)->getSettings()['target_type'];
      $deepEncoderClass = 'Drupal\\io_util\\Services\\Encoders\\'.ucfirst($type).'__'.$targetType.'Encoder';
      $deepEncoder = null;
      if(class_exists($deepEncoderClass)) {
        /** @var FieldEncoderInterface $encoder */
        $deepEncoder = new $deepEncoderClass;
      }
      if( $deepEncoder ) {
        $retVal = $deepEncoder->encodeItems($definition, $values);
      } else {
        if( !$values->isEmpty() ) {
          $err = "Can not deep-copy reference to type $targetType, a shallow-reference will be exported instead. ";
          Drupal\io_util\Services\DrupalExportUtils::$warnings[] = $err;
          echo "\n WARNING: $err Make sure target exists before importing.";
        }

        $retVal = $encoder->encodeItems($definition, $values);
      }
    }
    else {
      $retVal = $encoder->encodeItems($definition, $values);
    }

    return $retVal;
  }



}

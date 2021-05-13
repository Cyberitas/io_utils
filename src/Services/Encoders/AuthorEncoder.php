<?php


namespace Drupal\io_util\Services\Encoders;

//Unlike most encoders, 'author' is not a type. Thus, this is different.

use Drupal;

class AuthorEncoder extends AbstractFieldEncoder implements FieldEncoderInterface
{


  /**
   * @inheritDoc
   */
  public function encodeItem($value)
  {
    $authorEntity = Drupal::entityTypeManager()->getStorage('user')->load($value->target_id);
    // echo 'Saved author name is '.$authorEntity->get('name')->get(0)->getValue()['value']."\n";
    return [
      'name' => $authorEntity->get('name')->get(0)->getValue()['value'],
      'target_id' => $value->target_id,
    ];
  }
}


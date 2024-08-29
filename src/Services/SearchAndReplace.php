<?php

namespace Drupal\io_utils\Services;

use Drupal\block_content\Entity\BlockContent;
use Drupal\io_utils\Services\Decoders\FieldDecoderInterface;
use Drupal\io_utils\Services\Decoders\GenericDecoder;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class SearchAndReplace
{
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function findByRegex(string $search, array $restrictToFieldNames, array $moderationStates ): int
    {
        return $this->findAndReplace($search, null, $restrictToFieldNames, false, $moderationStates);
    }

    public function replaceByRegex( string $search, string $replace, array $restrictToFieldNames, array $moderationStates ): int
    {
        return $this->findAndReplace($search, $replace, $restrictToFieldNames, true, $moderationStates);
    }


    private function findAndReplace( string $search, ?string $replace, array $restrictToFieldNames, bool $bDoReplace = false, array $moderationStates = [] ): int
    {
        $iFoundEntity = 0;
        $aUnsupportedTypes = [];
        $nids = \Drupal::entityQuery('node')->execute();
        if ($nids) {
            $progressBar = new ProgressBar($this->output, count($nids));
            $progressBar->start();
            echo "\n";
            foreach ($nids as $nid) {

              $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
              $node = $nodeStorage->load($nid);

                if ($node) {
                    $url = $node->toUrl()->toString();
                    $moderationState = null;
                    try {
                        if( $node->get('moderation_state') ) {
                            $moderationState = $node->get('moderation_state')->value;
                        }
                    } catch( \Exception $e ) {
                        // ignore.
                    }

                    // Skip unpublished nodes if no moderation state is set.
                    if (empty($moderationStates)) {
                        if( !$node->isPublished() ) {
                            continue;
                        }
                    }
                    else {
                        if( !in_array($moderationState, $moderationStates) ) {
                            continue;
                        }
                    }


                    list($bHasEntity, $aLocations) = $this->checkFieldsForEntity($restrictToFieldNames, $search, $replace, $bDoReplace, $node, $aUnsupportedTypes);
                    if ($bHasEntity) {
                        echo "\n" . $url;
                        if( !$node->isPublished() && $moderationState ) {
                            echo ' (' . $moderationState . ')';
                        }
                        echo "\n";

                        echo implode("\n", $aLocations);
                        $iFoundEntity++;
                    }
                }
                $progressBar->advance();
            }
            $progressBar->finish();

        }

        if (sizeof($aUnsupportedTypes) > 0) {
            echo "\n\n** Entity types not checked [" . implode(", ", array_unique($aUnsupportedTypes)) . "]\n";
        }

        return $iFoundEntity;
    }

    /**
     * Regex based scanner to identify all entities that use embedded media entities.
     *
     * @param $restrictToFieldNames
     * @param $search string
     * @param string|null $replace
     * @param bool $bDoReplace
     * @param $entity
     * @param $aUnsupportedTypes
     * @return array
     */
    private function checkFieldsForEntity($restrictToFieldNames, string $search, ?string $replace, bool $bDoReplace, $entity, &$aUnsupportedTypes)
    {
        $bHasEntity = false;
        $aLocations = [];
        if ($entity && $entity->getEntityType() &&
            ($entity instanceof Node
                || $entity instanceof Paragraph
                || $entity instanceof BlockContent)
            && ($entity->getFields() != null)) {

            $bReplaced = false;
            foreach ($entity->getFields() as $name => $field) {
                if ($field->getFieldDefinition()->getTargetBundle()) {
                    $type = $field->getFieldDefinition()->getType();
                    if ($type == 'entity_reference' || $type == 'entity_reference_revisions') {
                        foreach ($field as $item) {
                            $referenced_entity = $item->get('entity')->getValue();
                            if ($referenced_entity != null) {
                                list($bChildHasEntity, $aChildLocations) = $this->checkFieldsForEntity($restrictToFieldNames, $search, $replace, $bDoReplace, $referenced_entity, $aUnsupportedTypes);
                                $bHasEntity |= $bChildHasEntity;
                                $aLocations = array_merge($aLocations, $aChildLocations);
                            }
                        }
                    }
                }
                $matches = [];

                if( empty($restrictToFieldNames) || in_array($name, $restrictToFieldNames) ) {

                    $properties =  $field->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinitions();
                    foreach( $properties as $propName => $propDefinition ) {
                        if( $propDefinition->getDataType() == 'string' ) {
                            if( !in_array( $propName, ['class', 'type', 'format', 'langcode', 'target_id']) ) {
                                foreach($field as $fieldId=>$fieldItem) {
                                    try {
                                        $old = $fieldItem->get($propName)->getValue();

                                        if (preg_match($search, $old, $matches)) {
                                            $bHasEntity |= true;
                                            $aLocations[] = "   * FOUND IN $name - [" . $entity->getEntityType()->id() . "]";
                                            // $aLocations[] = "     " . $matches[0];

                                            if ($bDoReplace) {
                                                $new = preg_replace($search, $replace, $old);
                                                $fieldItem->set($propName, $new);
                                                $bReplaced = true;
                                                $aLocations[] = "   * REPLACED IN $name - [" . $entity->getEntityType()->id() . "]";
                                            }
                                        }
                                    } catch(\Exception $e) {
                                        $aLocations[] = "   * Error processing field $name - [Entity ID:" . $entity->getEntityType()->id() . "]. " . $e->getMessage();
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if( $bReplaced ) {
                $entity->save();
            }
        } else {
            $aUnsupportedTypes[] = $entity->getEntityType()->id();
        }
        return [$bHasEntity, $aLocations];
    }
}

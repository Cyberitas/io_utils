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

    public function findByRegex(string $search, array $restrictToFieldNames ): int
    {
        return $this->findAndReplace($search, null, $restrictToFieldNames, false);
    }

    public function replaceByRegex( string $search, string $replace, array $restrictToFieldNames ): int
    {
        return $this->findAndReplace($search, $replace, $restrictToFieldNames, true);
    }


    private function findAndReplace( string $search, ?string $replace, array $restrictToFieldNames, bool $bDoReplace = false ): int
    {
        $iFoundEntity = 0;
        $aUnsupportedTypes = [];
        $nids = \Drupal::entityQuery('node')->execute();
        if ($nids) {
            $progressBar = new ProgressBar($this->output, count($nids));
            $progressBar->start();
            echo "\n";
            foreach ($nids as $nid) {
                $node = Node::load($nid);
                if ($node && $node->isPublished()) {
                    $url = $node->toUrl()->toString();
                    list($bHasEntity, $aLocations) = $this->checkFieldsForEntity($restrictToFieldNames, $search, $replace, $bDoReplace, $node, $aUnsupportedTypes);
                    if ($bHasEntity) {
                        echo "\n" . $url . "\n";
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
            foreach ($entity->getFields() as $name => $field) {
                if ($field->getFieldDefinition()->getTargetBundle()) {
                    $type = $field->getFieldDefinition()->getType();
                    if ($type == 'entity_reference' || $type == 'entity_reference_revisions') {
                        foreach ($field as $item) {
                            $referenced_entity = $item->entity;
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

                    foreach($field->getIterator() as $fieldId=>$fieldItem) {
                        $fieldValue = $fieldItem->getString();
                        if (preg_match($search, $fieldValue, $matches)) {
                            $bHasEntity |= true;
                            $aLocations[] = "   * FOUND IN $name - [" . $entity->getEntityType()->id() . "]";
                            // $aLocations[] = "     " . $matches[0];
                        }
                    }

                    if ($bDoReplace) {
                        if ($this->doReplace($entity, $field, $search, $replace)) {
                            $aLocations[] = "   * REPLACED IN $name - [" . $entity->getEntityType()->id() . "]";
                        } else {
                            $aLocations[] = "   * NOT REPLACED IN $name - [" . $entity->getEntityType()->id() . "]";
                        }
                    }
                }
            }

        } else {
            $aUnsupportedTypes[] = $entity->getEntityType()->id();
        }
        return [$bHasEntity, $aLocations];
    }

    private function doReplace( $entity, $field, $search, $replace ) {
        $bReplaced = false;

        try {
            $properties =  $field->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinitions();
            foreach( $properties as $propName => $propDefinition ) {
                if( $propDefinition->getDataType() == 'string' ) {
                    if( !in_array( $propName, ['class', 'type', 'format', 'langcode', 'target_id']) ) {
                        foreach($field->getIterator() as $fieldId=>$fieldItem) {
                            $old = $fieldItem->get($propName)->getValue();
                            $new = preg_replace($search, $replace, $old);
                            $fieldItem->set( $propName, $new );
                            $bReplaced = true;
                        }
                    }
                }
            }
            if( $bReplaced ) {
                $entity->save();
            }
        } catch (\Throwable $e) {
            echo "\n! Error: " . $e->getMessage() . "\n";
        }

        return $bReplaced;
    }
}

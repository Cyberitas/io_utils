<?php

namespace Drupal\io_utils\Services;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\block_content\Entity\BlockContent;

class SearchAndReplace
{
    protected $entityTypeManager;
    protected $entityQuery;
    protected $logger;

    public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory)
    {
        $this->entityTypeManager = $entityTypeManager;
        $this->entityQuery = $entityTypeManager->getStorage('node')->getQuery();
        $this->logger = $loggerFactory->get('io_utils');
    }

    public function findByRegex(string $search, array $restrictToFieldNames, array $moderationStates, $limit, $page = 0): array
    {
        \Drupal::logger('io_utils')->notice('findByRegex called'); //TODO: Debugging
        return $this->findAndReplace($search, null, $restrictToFieldNames, false, $moderationStates, $limit, $page);
    }

    public function replaceByRegex(string $search, string $replace, array $restrictToFieldNames, array $moderationStates, $limit, $page = 0): array
    {
        return $this->findAndReplace($search, $replace, $restrictToFieldNames, true, $moderationStates, $limit, $page);

    }

    /**
     * Searches for a string and optionally replaces it within specified fields.
     *
     * @param string $search The string to search for
     * @param string|null $replace The string to replace with (null if no replacement)
     * @param array $restrictToFieldNames Array of field names to restrict the search to
     * @param bool $bDoReplace Whether to perform the replacement (default: false)
     * @param array $moderationStates Array of moderation states to filter by (default: empty array)
     * @param int $limit Maximum number of results to return (default: 10)
     * @param int $page Page number for pagination (default: 0)
     * @return array{
     *     count: int,
     *     matches: array{
     *         url: string,
     *         type: string,
     *         title: string,
     *         locations: array{
     *             status: string,
     *             message: string
     *         },
     *         published: string,
     *         moderation_state: string
     *     }
     * } An array containing 'count' (total matches) and 'matches' (array of matching items)
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     * @throws EntityMalformedException
     */

    private function findAndReplace(string $search, ?string $replace, array $restrictToFieldNames, bool $bDoReplace = false, array $moderationStates = [], int $limit = 10, int $page = 0): array
    {
        $results = [
            'count' => 0,
            'matches' => [],
        ];
        $aUnsupportedTypes = [];
        $nids = $this->entityQuery->execute();

        if ($nids) {
            $this->logger->info("Starting search and replace operation.");
            $total_count = 0;
            $offset = $page * $limit;
            $processed_count = 0;

            foreach ($nids as $nid) {
                $nodeStorage = $this->entityTypeManager->getStorage('node');
                $node = $nodeStorage->load($nid);

                if ($node) {
                    $url = $node->toUrl()->toString();
                    $moderationState = null;
                    try {
                        if ($node->hasField('moderation_state')) {
                            $moderationState = $node->get('moderation_state')->value;
                        }
                    } catch (\Exception $e) {
                        // ignore.
                    }

                    if (empty($moderationStates)) {
                        if (!$node->isPublished()) {
                            continue;
                        }
                    } else {
                        if (!in_array($moderationState, $moderationStates)) {
                            continue;
                        }
                    }

                    list($bHasEntity, $aLocations) = $this->checkFieldsForEntity($restrictToFieldNames, $search, $replace, $bDoReplace, $node, $aUnsupportedTypes);
                    if ($bHasEntity) {
                        $total_count++;
                        if ($total_count > $offset && $processed_count < $limit) {
                            $formattedLocations = [];
                            foreach ($aLocations as $location) {
                                $formattedLocations[] = $location['message'];
                            }
                            $results['matches'][] = [
                                'url' => $url,
                                'type' => $node->getType(),
                                'title' => $node->getTitle(),
                                'locations' => $aLocations,  // Use the full $aLocations array here
                                'published' => $node->isPublished(),
                                'moderation_state' => $moderationState,
                            ];
                            $processed_count++;

//                            $this->logger->info("Match found at {url}", ['url' => $url]);
//                            if (!$node->isPublished() && $moderationState) {
//                                $this->logger->info('Moderation state: {state}', ['state' => $moderationState]);
//                            }
//                            $this->logger->info(implode("\n", $formattedLocations));
                        }
                    }
                }
            }
            $results['count'] = $total_count;
            $this->logger->info("Search and replace operation completed. Total matches: {count}", ['count' => $total_count]);
        }

        if (sizeof($aUnsupportedTypes) > 0) {
            $results['unsupported_types'] = array_unique($aUnsupportedTypes);
            $this->logger->warning("Entity types not checked: {types}", ['types' => implode(", ", $results['unsupported_types'])]);
        }

        return $results;
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

    private function checkFieldsForEntity(array $restrictToFieldNames, string $search, ?string $replace, bool $bDoReplace, $entity, &$aUnsupportedTypes): array
    {
        $bHasEntity = false;
        $aLocations = [];
        if ($entity && $entity->getEntityType() &&
            ($entity instanceof Node || $entity instanceof Paragraph || $entity instanceof BlockContent) &&
            ($entity->getFields() != null)) {

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

                if (empty($restrictToFieldNames) || in_array($name, $restrictToFieldNames)) {
                    $properties = $field->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinitions();
                    foreach ($properties as $propName => $propDefinition) {
                        if ($propDefinition->getDataType() == 'string') {
                            if (!in_array($propName, ['class', 'type', 'format', 'langcode', 'target_id'])) {
                                foreach ($field as $fieldId => $fieldItem) {
                                    try {
                                        $old = $fieldItem->get($propName)->getValue();
                                        if (preg_match($search, $old, $matches)) {
                                            $bHasEntity |= true;
                                            $aLocations[] = [
                                                'status' => 'search',
                                                'message' => "   * FOUND IN $name - [" . $entity->getEntityType()->id() . "]"
                                            ];

                                        if ($bDoReplace) {
//                                            // Throw an exception for block content entries" TODO: Test Code Remove!
//                                            if ($entity->getEntityType()->id() === 'block_content') {
//                                                throw new \Exception('Simulated error for testing purposes');
//                                            }
                                            $new = preg_replace($search, $replace, $old);
                                            $fieldItem->set($propName, $new);
                                            $bReplaced = true;
                                            $aLocations[] = [
                                                'status' => 'replace',
                                                'message' => "   * REPLACED IN $name - [" . $entity->getEntityType()->id() . "]"
                                            ];
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        $this->logger->error("Error processing field {field} - [Entity ID: {id}]. {message}", [
                                            'field' => $name,
                                            'id' => $entity->getEntityType()->id(),
                                            'message' => $e->getMessage(),
                                        ]);
                                        $aLocations[] = [
                                            'status' => 'resumable error',
                                            'message' => "   * Error processing field $name - [Entity ID:" . $entity->getEntityType()->id() . "]. " . $e->getMessage()
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($bReplaced) {
                $entity->save();
            }
        } else {
            $aUnsupportedTypes[] = $entity->getEntityType()->id();
        }

        return [$bHasEntity, $aLocations];
    }
}

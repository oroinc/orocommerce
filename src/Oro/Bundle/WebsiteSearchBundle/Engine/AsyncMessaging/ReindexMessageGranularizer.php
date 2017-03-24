<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;

class ReindexMessageGranularizer
{
    use ContextTrait;

    /**
     * The maximum number of IDs per one reindex request message
     */
    const ID_CHUNK_SIZE = 100;

    /**
     * @param array|string $entities
     * @param array $websites
     * @param array $context
     * $context = [
     *     'entityIds' int[] Array of entities ids to reindex
     * ]
     *
     * @return array
     */
    public function process($entities, array $websites, array $context)
    {
        $entities = (array)$entities;

        $entityIds = $this->getContextEntityIds($context);

        $result = [];

        if (empty($websites)) {
            foreach ($entities as $entity) {
                $chunks = $this->getChunksOfIds($entityIds);
                foreach ($chunks as $chunk) {
                    $itemContext = [];
                    $itemContext = $this->setContextEntityIds($itemContext, $chunk);
                    $result[]    = [
                        'class'   => [$entity],
                        'context' => $itemContext
                    ];
                }
            }

            return $result;
        }

        foreach ($websites as $website) {
            foreach ($entities as $entity) {
                $chunks = $this->getChunksOfIds($entityIds);
                foreach ($chunks as $chunk) {
                    $itemContext = [];
                    $itemContext = $this->setContextEntityIds($itemContext, $chunk);
                    $itemContext = $this->setContextWebsiteIds($itemContext, [$website]);
                    $result[]    = [
                        'class'   => [$entity],
                        'context' => $itemContext
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * @param array $ids
     * @return array
     */
    private function getChunksOfIds($ids)
    {
        if (empty($ids)) {
            return [[]]; // this will make the iteration go through
        }
        return array_chunk($ids, self::ID_CHUNK_SIZE);
    }
}

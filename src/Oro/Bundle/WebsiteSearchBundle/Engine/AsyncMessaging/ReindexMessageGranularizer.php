<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\EntityIdentifierRepository;

/**
 * Splats given list of entities on chunks applicable and optimized for indexation process.
 */
class ReindexMessageGranularizer
{
    use ContextTrait;

    /**
     * The maximum number of IDs per one reindex request message
     *
     * @var int
     */
    private $chunkSize = 100;

    /**
     * @var EntityIdentifierRepository
     */
    private $identifierRepository;

    public function __construct(EntityIdentifierRepository $identifierRepository)
    {
        $this->identifierRepository = $identifierRepository;
    }

    /**
     * @param int $chunkSize
     */
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;
    }

    /**
     * @param array|string $entities
     * @param array $websites
     * @param array $context
     * $context = [
     *     'entityIds' int[] Array of entities ids to reindex
     * ]
     *
     * @return iterable<array{class: array, context: array}>
     */
    public function process($entities, array $websites, array $context): iterable
    {
        $entities = (array)$entities;

        $entityIds = (array)$this->getContextEntityIds($context);

        if (empty($websites)) {
            foreach ($entities as $entity) {
                $chunks = $this->getChunksOfIds($entity, $entityIds);
                foreach ($chunks as $chunk) {
                    $itemContext = [];
                    $itemContext = $this->setContextEntityIds($itemContext, $chunk);
                    $itemContext = $this->setContextFieldGroups($itemContext, $this->getContextFieldGroups($context));
                    yield [
                        'class'   => [$entity],
                        'context' => $itemContext,
                    ];
                }
            }
        }

        foreach ($websites as $website) {
            foreach ($entities as $entity) {
                $chunks = $this->getChunksOfIds($entity, $entityIds);
                foreach ($chunks as $chunk) {
                    $itemContext = [];
                    $itemContext = $this->setContextEntityIds($itemContext, $chunk);
                    $itemContext = $this->setContextWebsiteIds($itemContext, [$website]);
                    $itemContext = $this->setContextFieldGroups($itemContext, $this->getContextFieldGroups($context));
                    yield [
                        'class'   => [$entity],
                        'context' => $itemContext,
                    ];
                }
            }
        }
    }

    /**
     * @param string $entityClass
     * @param array $ids
     * @return iterable|array
     */
    private function getChunksOfIds($entityClass, array $ids)
    {
        if (empty($ids)) {
            $ids = $this->identifierRepository->getIds($entityClass);
        }
        //  Split generator into chunks as generator
        $chunk = [];
        foreach ($ids as $id) {
            $chunk[] = $id;
            if (count($chunk) >= $this->chunkSize) {
                yield $chunk;
                $chunk = [];
            }
        }
        if ([] !== $chunk) {
            // Remaining chunk with fewer items.
            yield $chunk;
        }

        return [];
    }
}

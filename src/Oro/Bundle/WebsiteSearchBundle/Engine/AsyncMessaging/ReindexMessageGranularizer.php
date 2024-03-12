<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\EntityIdentifierRepository;

/**
 * Splits given list of entities on chunks applicable and optimized for indexation process.
 */
class ReindexMessageGranularizer
{
    use ContextTrait;

    private int $chunkSize = 100;

    public function __construct(private EntityIdentifierRepository $identifierRepository)
    {
    }

    public function setChunkSize(int $chunkSize): void
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
    public function process(array|string $entities, array $websites, array $context): iterable
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

        foreach ($entities as $entity) {
            $chunks = $this->getChunksOfIds($entity, $entityIds);
            foreach ($chunks as $chunk) {
                foreach ($websites as $website) {
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

    private function getChunksOfIds(string $entityClass, array $ids)
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

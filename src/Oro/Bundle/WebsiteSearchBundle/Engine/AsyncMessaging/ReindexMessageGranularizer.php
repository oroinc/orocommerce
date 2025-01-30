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

    private const int CHUNK_SIZE = 100;

    private int $chunkSize = self::CHUNK_SIZE;

    public function __construct(private EntityIdentifierRepository $identifierRepository)
    {
    }

    public function setChunkSize(int $chunkSize): void
    {
        $this->chunkSize = $chunkSize;
    }

    /**
     * $context = [
     *     'entityIds' int[] Array of entities ids to reindex
     * ]
     *
     * @return iterable<array{class: array, context: array}>
     */
    public function process(array|string $entities, array $websiteIds, array $context): iterable
    {
        $entities = (array)$entities;
        $entityIds = $this->getContextEntityIds($context);

        $this->updateChunkSizeByContext($context);

        foreach ($entities as $entity) {
            $chunks = $this->getChunksOfIds($entity, $entityIds);
            foreach ($chunks as $chunk) {
                if (empty($websiteIds)) {
                    yield $this->buildMessage($entity, $context, $chunk);
                    continue;
                }

                foreach ($websiteIds as $websiteId) {
                    yield $this->buildMessage($entity, $context, $chunk, $websiteId);
                }
            }
        }

        $this->resetChunkSize();
    }

    private function buildMessage(string $entityClass, array $context, array $chunk, ?int $websiteId = null): array
    {
        $itemContext = [];
        $itemContext = $this->setContextEntityIds($itemContext, $chunk);
        $itemContext = $this->setContextFieldGroups($itemContext, $this->getContextFieldGroups($context));
        $itemContext = $this->setContextBatchSize($itemContext, $this->getContextBatchSize($context));

        if ($websiteId) {
            $itemContext = $this->setContextWebsiteIds($itemContext, [$websiteId]);
        }

        return [
            'class'   => [$entityClass],
            'context' => $itemContext
        ];
    }

    private function getChunksOfIds(string $entityClass, array $ids): iterable
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

    private function updateChunkSizeByContext(array $context): void
    {
        $batchSize = $this->getContextBatchSize($context);
        if ($batchSize) {
            $this->chunkSize = $batchSize;
        }
    }

    private function resetChunkSize(): void
    {
        $this->chunkSize = self::CHUNK_SIZE;
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\BatchBundle\Tools\ChunksHelper;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentVariantRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * Removes orphan slugs when WebCatalog is deleted.
 *
 * As ContentNodes and ContentVariants are deleted via database CASCADE,
 * not through Doctrine ORM, so orphanRemoval doesn't work for Slugs.
 */
class WebCatalogSlugRemoveListener
{
    private const BATCH_SIZE = 1000;

    public function __construct(
        private ContentVariantRepository $contentVariantRepository,
        private SlugRepository $slugRepository
    ) {
    }

    public function preRemove(WebCatalog $webCatalog): void
    {
        $qb = $this->contentVariantRepository->getSlugIdsByWebCatalogQueryBuilder($webCatalog->getId());
        $iterator = $this->createIterator($qb);

        foreach (ChunksHelper::splitInChunksByColumn($iterator, self::BATCH_SIZE, 'id') as $batch) {
            $this->slugRepository->deleteByIds($batch);
        }
    }

    protected function createIterator(QueryBuilder $qb): BufferedQueryResultIteratorInterface
    {
        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setBufferSize(self::BATCH_SIZE);

        return $iterator;
    }
}

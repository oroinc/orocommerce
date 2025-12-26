<?php

namespace Oro\Bundle\CatalogBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Schedules product reindex and clears a cache for category layout data provider
 * when a Category entity is created, removed or changed.
 *
 * Deletes descendant category slugs manually.
 * The cascade caused a bug with Gedmo nested set:
 * when deleting a category, Doctrine scheduled children first, Gedmo's shiftRL()
 * updated tree indices for each child, and by the time parent was processed,
 * sibling categories had shifted into the parent's tree range and got deleted too.
 */
class CategoryEntityListener
{
    public function __construct(
        private ProductIndexScheduler $productIndexScheduler,
        private CacheInterface $categoryCache,
        private ManagerRegistry $doctrine
    ) {
    }

    public function preRemove(Category $category): void
    {
        $slugIds = $this->getCategoryRepository()->getDescendantSlugIds($category);
        $this->getSlugRepository()->deleteByIds($slugIds);
        $this->scheduleCategoryReindex($category);
    }

    public function postPersist(Category $category): void
    {
        $this->scheduleCategoryReindex($category);
    }

    public function preUpdate(Category $category, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->getEntityChangeSet()) {
            $this->scheduleCategoryReindex($category);
        }
    }

    private function scheduleCategoryReindex(Category $category): void
    {
        $this->productIndexScheduler->scheduleProductsReindex([$category], null, true, ['main', 'inventory']);
        $this->categoryCache->clear();
    }

    private function getCategoryRepository(): CategoryRepository
    {
        return $this->doctrine->getRepository(Category::class);
    }

    private function getSlugRepository(): SlugRepository
    {
        return $this->doctrine->getRepository(Slug::class);
    }
}

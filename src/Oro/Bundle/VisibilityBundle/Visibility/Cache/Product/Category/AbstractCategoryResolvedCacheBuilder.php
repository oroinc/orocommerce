<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\AbstractResolvedCacheBuilder;

/**
 * The base class for category visibility cache builders.
 */
abstract class AbstractCategoryResolvedCacheBuilder extends AbstractResolvedCacheBuilder
{
    private ProductIndexScheduler $indexScheduler;

    public function __construct(
        ManagerRegistry $doctrine,
        ProductIndexScheduler $indexScheduler
    ) {
        parent::__construct($doctrine);
        $this->indexScheduler = $indexScheduler;
    }

    protected function convertVisibility(bool $isVisible): int
    {
        return $isVisible
            ? BaseVisibilityResolved::VISIBILITY_VISIBLE
            : BaseVisibilityResolved::VISIBILITY_HIDDEN;
    }

    protected function convertStaticVisibility(string $visibility): int
    {
        return $visibility === VisibilityInterface::VISIBLE
            ? BaseVisibilityResolved::VISIBILITY_VISIBLE
            : BaseVisibilityResolved::VISIBILITY_HIDDEN;
    }

    protected function indexVisibilities(array $visibilities, string $fieldName): array
    {
        $indexedVisibilities = [];
        foreach ($visibilities as $visibility) {
            $index = $visibility[$fieldName];
            $indexedVisibilities[$index] = $visibility;
        }

        return $indexedVisibilities;
    }

    protected function triggerCategoriesReindexation(array $categories): void
    {
        $this->indexScheduler->scheduleProductsReindex($categories, null, true, ['visibility']);
    }
}

<?php

namespace Oro\Bundle\VisibilityBundle\Entity\EntityListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;

/**
 * This listener will add one record of category visibility resolved as parent's visibility as default.
 * This is in order to prevent a category added which visibility is as parent aspect but global one is turning off.
 * When a category has its own visibility entity, this default one will be overwritten normally.
 */
class CategoryDefaultVisibilityListener
{
    private array $categoriesForUpdate = [];

    public function __construct(private ScopeManager $scopeManager, private InsertFromSelectQueryExecutor $executor)
    {
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $objectManager = $event->getObjectManager();
        $unitOfWork = $objectManager->getUnitOfWork();
        $entities = $unitOfWork->getScheduledEntityInsertions();

        $categories = array_filter($entities, static fn ($entity) => $entity instanceof Category);
        $repository = $objectManager->getRepository(CategoryVisibilityResolved::class);
        foreach ($categories as $category) {
            if ($parentCategory = $this->getParentCategory($category)) {
                $visibility = $repository->getFallbackToAllVisibility($parentCategory);
                $this->categoriesForUpdate[] = ['visibility' => $visibility, 'category' => $category];
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $objectManager = $args->getObjectManager();
        $repository = $objectManager->getRepository(CategoryVisibilityResolved::class);
        $scope = $this->scopeManager->findDefaultScope();

        foreach ($this->categoriesForUpdate as $categoryUpdate) {
            $category = $categoryUpdate['category'];
            $visibility = $categoryUpdate['visibility'];
            if (!$repository->find(['category' => $category, 'scope' => $scope])) {
                $repository->insertParentCategoryValues($this->executor, [$category->getId()], $visibility, $scope);
            }
        }
    }

    private function getParentCategory(Category $category): ?Category
    {
        if (!$category->getParentCategory()) {
            return null;
        }

        if ($category->getParentCategory()->getId()) {
            return $category->getParentCategory();
        }

        return $this->getParentCategory($category->getParentCategory());
    }
}

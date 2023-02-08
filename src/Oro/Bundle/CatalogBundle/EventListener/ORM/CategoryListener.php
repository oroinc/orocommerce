<?php

namespace Oro\Bundle\CatalogBundle\EventListener\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Model\CategoryMaterializedPathModifier;

/**
 * Updates materializedPath of changed categories
 */
class CategoryListener
{
    /**
     * @var CategoryMaterializedPathModifier
     */
    protected $modifier;

    /** @var \SplObjectStorage */
    private $scheduled;

    public function __construct(CategoryMaterializedPathModifier $modifier)
    {
        $this->modifier = $modifier;
        $this->scheduled = new \SplObjectStorage();
    }

    public function postPersist(Category $category, LifecycleEventArgs $event)
    {
        $entityManager = $event->getEntityManager();

        // Handles parent category materialized path.
        $parentCategory = $category->getParentCategory();
        while ($parentCategory && !$parentCategory->getMaterializedPath()) {
            $this->modifier->calculateMaterializedPath($parentCategory);
            $this->scheduleExtraUpdate($entityManager, $parentCategory, $parentCategory->getMaterializedPath());
        }

        $this->modifier->calculateMaterializedPath($category);
        $this->scheduleExtraUpdate($entityManager, $category, $category->getMaterializedPath());

        if (!empty($this->scheduled[$category])) {
            $children = array_merge(...$this->scheduled[$category]);
            /** @var Category $child */
            foreach ($children as $child) {
                $this->modifier->calculateMaterializedPath($child);
                $this->scheduleExtraUpdate($entityManager, $child, $child->getMaterializedPath());
            }
        }
    }

    private function scheduleExtraUpdate(
        EntityManager $entityManager,
        Category $category,
        string $materializedPath
    ): void {
        $unitOfWork = $entityManager->getUnitOfWork();
        $categoryClassMetadata = $entityManager->getClassMetadata(Category::class);

        $unitOfWork->propertyChanged($category, 'materializedPath', null, $materializedPath);
        $unitOfWork->scheduleExtraUpdate($category, ['materializedPath' => [null, $materializedPath]]);
        $unitOfWork->recomputeSingleEntityChangeSet($categoryClassMetadata, $category);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $entityManager = $event->getEntityManager();
        $metadataFactory = $entityManager->getMetadataFactory();

        /** @var CategoryRepository $categoryRepository */
        $unitOfWork = $entityManager->getUnitOfWork();
        $entities = $unitOfWork->getScheduledEntityUpdates();
        $categoryRepository = $entityManager->getRepository(Category::class);
        $categoryClassMetadata = $metadataFactory->getMetadataFor(Category::class);

        foreach ($entities as $entity) {
            if (!$entity instanceof Category) {
                continue;
            }

            $changeSet = $unitOfWork->getEntityChangeSet($entity);
            if (!empty($changeSet[Category::FIELD_PARENT_CATEGORY]) || empty($entity->getMaterializedPath())) {
                $parentCategory = $entity->getParentCategory();
                $categoryChildren = $categoryRepository->children($entity);

                if (!$parentCategory || $parentCategory->getId()) {
                    // Parent category has id, materialized path can be recalculated.
                    $this->modifier->calculateMaterializedPath($entity);
                    $unitOfWork->recomputeSingleEntityChangeSet($categoryClassMetadata, $entity);

                    // Updates children materialized path.
                    foreach ($categoryChildren as $child) {
                        $this->modifier->calculateMaterializedPath($child);
                        $unitOfWork->recomputeSingleEntityChangeSet($categoryClassMetadata, $child);
                    }
                } else {
                    // Parent category does not have id, materialized path cannot be recalculated right now.
                    if (!isset($this->scheduled[$parentCategory])) {
                        $this->scheduled[$parentCategory] = [];
                    }

                    // Schedules recalculation for later, will be handled in postPersist.
                    $children = $this->scheduled[$parentCategory];
                    $children[] = [$entity];
                    $children[] = $categoryChildren;
                    $this->scheduled[$parentCategory] = $children;
                }
            }
        }
    }
}

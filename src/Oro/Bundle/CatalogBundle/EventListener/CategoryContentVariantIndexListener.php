<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Event\OnFlushEventArgs;

use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryContentVariantIndexListener
{
    /** @var ProductIndexScheduler */
    protected $indexScheduler;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @param ProductIndexScheduler $indexScheduler
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(ProductIndexScheduler $indexScheduler, PropertyAccessorInterface $accessor)
    {
        $this->indexScheduler = $indexScheduler;
        $this->accessor = $accessor;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();

        $categories = [];
        $this->collectCategories($unitOfWork->getScheduledEntityInsertions(), $categories, $unitOfWork);
        $this->collectCategories($unitOfWork->getScheduledEntityUpdates(), $categories, $unitOfWork);
        $this->collectCategories($unitOfWork->getScheduledEntityDeletions(), $categories, $unitOfWork);

        if ($categories) {
             $this->indexScheduler->scheduleProductsReindex($categories);
        }
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function onFormAfterFlush(AfterFormProcessEvent $event)
    {
        $node = $event->getData();
        if (!$node instanceof ContentNodeInterface) {
            return;
        }

        $categories = [];

        $this->collectCategories($node->getContentVariants(), $categories);

        if ($categories) {
            $this->indexScheduler->scheduleProductsReindex($categories);
        }
    }

    /**
     * @param array|Collection $entities
     * @param Category[] &$categories
     * @param UnitOfWork $unitOfWork
     */
    protected function collectCategories($entities, array &$categories, $unitOfWork = null)
    {
        foreach ($entities as $entity) {
            if ($entity instanceof ContentVariantInterface
                && $entity->getType() === CategoryPageContentVariantType::TYPE
            ) {
                /** @var Category $category */
                $category = $this->accessor->getValue($entity, 'category_page_category');
                if ($category) {
                    $this->addCategory($categories, $category);
                }

                if ($unitOfWork) {
                    $changeSet = $unitOfWork->getEntityChangeSet($entity);
                    if (array_key_exists('category_page_category', $changeSet)) {
                        $this->addCategory($categories, $changeSet['category_page_category'][0]);
                        $this->addCategory($categories, $changeSet['category_page_category'][1]);
                    }
                }
            }
        }
    }

    /**
     * @param Category[] &$categories
     * @param Category $category
     */
    protected function addCategory(array &$categories, Category $category)
    {
        $categoryId = $category->getId();
        if ($categoryId && !array_key_exists($categoryId, $categories)) {
            $categories[$categoryId] = $category;
        }
    }
}

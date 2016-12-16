<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

use Doctrine\Common\Collections\Collection;
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
        $categories = $this->collectCategories($unitOfWork->getScheduledEntityInsertions(), $categories);
        $categories = $this->collectCategories($unitOfWork->getScheduledEntityUpdates(), $categories);
        $categories = $this->collectCategories($unitOfWork->getScheduledEntityDeletions(), $categories);

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

        $categories = $this->collectCategories($node->getContentVariants(), []);

        if ($categories) {
            $this->indexScheduler->scheduleProductsReindex($categories);
        }
    }

    /**
     * @param array|Collection $entities
     * @param Category[] $categories
     * @return Category[]
     */
    protected function collectCategories($entities, array $categories)
    {
        foreach ($entities as $entity) {
            if ($entity instanceof ContentVariantInterface
                && $entity->getType() === CategoryPageContentVariantType::TYPE
            ) {
                /** @var Category $category */
                $category = $this->accessor->getValue($entity, 'category_page_category');
                if ($category && $category->getId() && !array_key_exists($category->getId(), $categories)) {
                    $categories[$category->getId()] = $category;
                }
            }
        }

        return $categories;
    }
}

<?php

namespace Oro\Bundle\AccountBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\AccountBundle\Async\Topics;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Model\CategoryMessageHandler;
use Oro\Bundle\ProductBundle\Entity\Product;

class CategoryListener
{
    const FIELD_PRODUCTS = 'products';
    const FIELD_RARENT_CATEGORY = 'parentCategory';

    /**
     * @var CategoryMessageHandler
     */
    protected $categoryMessageHandler;

    /**
     * @param CategoryMessageHandler $categoryMessageHandler
     */
    public function __construct(
        CategoryMessageHandler $categoryMessageHandler

    ) {
        $this->categoryMessageHandler = $categoryMessageHandler;
    }

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $event)
    {
        $this->handleProductsChange($event);
        $this->handleParentCategoryChange($event);
    }

    public function postUpdate()
    {
        //TODO: add Product message handler
//        $repository = $this->registry->getManagerForClass('OroProductBundle:Product')
//            ->getRepository('OroProductBundle:Product');
//
//        while ($productId = array_shift($this->productIdsToUpdate)) {
//            $product = $repository->find($productId);
//            if ($product) {
//                $this->cacheBuilder->productCategoryChanged($product);
//            }
//        }
    }

    public function postRemove()
    {
        /** @var Category $category */
        if ($category instanceof Category) {
            $this->categoryMessageHandler->addCategoryMessageToSchedule(Topics::CATEGORY_REMOVE);
        }
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    protected function handleProductsChange(PreUpdateEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $collections = $unitOfWork->getScheduledCollectionUpdates();
        $chan = $event->hasChangedField(self::FIELD_PRODUCTS);
        foreach ($collections as $collection) {
            if ($collection instanceof PersistentCollection
                && $collection->getMapping()['fieldName'] === self::FIELD_PRODUCTS
                && $collection->isDirty() && $collection->isInitialized()
            ) {
                /** @var Product $product */
                foreach (array_merge($collection->getInsertDiff(), $collection->getDeleteDiff()) as $product) {
//                    $this->productIdsToUpdate[$product->getId()] = $product->getId();
                }
            }
        }
    }

    /**
     * @param PreUpdateEventArgs $event
     */
    protected function handleParentCategoryChange(PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(self::FIELD_RARENT_CATEGORY)) {
            $this->categoryMessageHandler->addCategoryMessageToSchedule(
                Topics::CHANGE_CATEGORY_VISIBILITY,
                $event->getEntity()
            );
        }
    }
}

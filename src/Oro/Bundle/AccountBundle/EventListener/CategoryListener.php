<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;

class CategoryListener
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var ProductCaseCacheBuilderInterface
     */
    protected $cacheBuilder;

    /**
     * @var array
     */
    protected $productIdsToUpdate = [];

    /**
     * @param Registry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param ProductCaseCacheBuilderInterface $cacheBuilder
     */
    public function __construct(
        Registry $registry,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        ProductCaseCacheBuilderInterface $cacheBuilder
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->cacheBuilder = $cacheBuilder;
    }

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $unitOfWork = $event->getEntityManager()->getUnitOfWork();
        $collections = $unitOfWork->getScheduledCollectionUpdates();
        foreach ($collections as $collection) {
            if ($collection instanceof PersistentCollection && $collection->getOwner() instanceof Category
                && $collection->getMapping()['fieldName'] === 'products'
                && $collection->isDirty() && $collection->isInitialized()
            ) {
                /** @var Product $product */
                foreach (array_merge($collection->getInsertDiff(), $collection->getDeleteDiff()) as $product) {
                    $productId = $product->getId();
                    if (!in_array($productId, $this->productIdsToUpdate)) {
                        $this->productIdsToUpdate[] = $productId;
                    }
                }
            }
        }
    }

    public function postFlush()
    {
        $repository = $this->registry->getManagerForClass('OroProductBundle:Product')
            ->getRepository('OroProductBundle:Product');

        while ($productId = array_shift($this->productIdsToUpdate)) {
            $product = $repository->find($productId);
            if ($product) {
                $this->cacheBuilder->productCategoryChanged($product);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        /** @var Category $category */
        $category = $args->getEntity();
        if ($category instanceof Category) {
            $this->setToDefaultProductVisibilityWithoutCategory();
            $this->setToDefaultAccountGroupProductVisibilityWithoutCategory();
            $this->setToDefaultAccountProductVisibilityWithoutCategory();
        }
    }

    protected function setToDefaultProductVisibilityWithoutCategory()
    {
        $this->registry->getManagerForClass('OroAccountBundle:Visibility\ProductVisibility')
            ->getRepository('OroAccountBundle:Visibility\ProductVisibility')
            ->setToDefaultWithoutCategory($this->insertFromSelectQueryExecutor);
    }

    protected function setToDefaultAccountGroupProductVisibilityWithoutCategory()
    {
        $this->registry->getManagerForClass('OroAccountBundle:Visibility\AccountGroupProductVisibility')
            ->getRepository('OroAccountBundle:Visibility\AccountGroupProductVisibility')
            ->setToDefaultWithoutCategory();
    }

    protected function setToDefaultAccountProductVisibilityWithoutCategory()
    {
        $this->registry->getManagerForClass('OroAccountBundle:Visibility\AccountProductVisibility')
            ->getRepository('OroAccountBundle:Visibility\AccountProductVisibility')
            ->setToDefaultWithoutCategory();
    }
}

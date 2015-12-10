<?php

namespace OroB2B\Bundle\AccountBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

class CategoryListener
{
    /** @var Registry */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @param Registry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     */
    public function __construct(Registry $registry, InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor)
    {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        /** @var Category $category */
        $category = $args->getEntity();
        if ($category instanceof Category) {
            $this->updateToConfigProductVisibility();
            $this->setToDefaultValueProductAccountGroupProductVisibilityForProductsWithoutCategory();
            $this->setToDefaultValueAccountProductVisibilityForProductsWithoutCategory();
        }
    }

    protected function updateToConfigProductVisibility()
    {
        $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\ProductVisibility')
            ->updateToConfigProductVisibility($this->insertFromSelectQueryExecutor);
    }

    protected function setToDefaultValueProductAccountGroupProductVisibilityForProductsWithoutCategory()
    {
        $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->setToDefaultValueProductAccountGroupProductVisibilityForProductsWithoutCategory();
    }

    protected function setToDefaultValueAccountProductVisibilityForProductsWithoutCategory()
    {
        $this->registry->getManagerForClass('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->setToDefaultValueAccountProductVisibilityForProductsWithoutCategory();
    }
}

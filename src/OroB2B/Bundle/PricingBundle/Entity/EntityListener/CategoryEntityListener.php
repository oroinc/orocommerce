<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\PricingBundle\Builder\PriceListProductAssignmentBuilder;
use OroB2B\Bundle\PricingBundle\Builder\ProductPriceBuilder;

class CategoryEntityListener
{
    /**
     * @var array
     */
    protected $createdTriggers = [];

    /**
     * @var PriceListProductAssignmentBuilder
     */
    protected $productAssignmentBuilder;

    /**
     * @var ProductPriceBuilder
     */
    protected $productPriceBuilder;

    /**
     * @param Category $category
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Category $category, PreUpdateEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $sc = $uow->getScheduledEntityUpdates();
        $cs = $uow->getEntityChangeSet($category);
        $x = 1;
    }

    /**
     * @param Category $category
     * @param LifecycleEventArgs $event
     */
    public function preRemove(Category $category, LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        $ancestorCategories = $em->getRepository(Category::class)->getRootNodes();
    }
}

<?php

namespace Oro\Bundle\AccountBundle\Entity\EntityListener;

use Oro\Bundle\AccountBundle\Async\Topics;
use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\AccountBundle\Model\VisibilityTriggerHandler;

class ProductVisibilityListener
{
    /**
     * @var VisibilityTriggerHandler
     */
    protected $productVisibilityChangeTriggerHandler;

    /**
     * @param VisibilityTriggerHandler $productVisibilityChangeTriggerHandler
     */
    public function __construct(VisibilityTriggerHandler $productVisibilityChangeTriggerHandler)
    {
        $this->productVisibilityChangeTriggerHandler = $productVisibilityChangeTriggerHandler;
    }
    
    /**
     * Recalculate visibilities on product visibility change.
     *
     * @param VisibilityInterface $productVisibility
     */
    public function postPersist(VisibilityInterface $productVisibility)
    {
        $this->productVisibilityChangeTriggerHandler->addTriggersForProductVisibility(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $productVisibility
        );
    }

    /**
     * Recalculate visibilities on product visibility change.
     *
     * @param VisibilityInterface $productVisibility
     */
    public function preUpdate(VisibilityInterface $productVisibility)
    {
        $this->productVisibilityChangeTriggerHandler->addTriggersForProductVisibility(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $productVisibility
        );
    }

    /**
     * Recalculate visibilities on product visibility remove.
     *
     * @param VisibilityInterface $productVisibility
     */
    public function preRemove(VisibilityInterface $productVisibility)
    {
        $this->productVisibilityChangeTriggerHandler->addTriggersForProductVisibility(
            Topics::RESOLVE_PRODUCT_VISIBILITY,
            $productVisibility
        );
    }
}

<?php

namespace OroB2B\Bundle\CheckoutBundle\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class CheckoutEvent
{
    /**
     * @var object
     */
    protected $checkoutEntity;

    /**
     * @var WorkflowItem
     */
    protected $workflowItem;

    /**
     * CheckoutEvent constructor.
     * @param WorkflowItem $workflowItem
     */
    public function __construct(WorkflowItem $workflowItem)
    {
        $this->workflowItem = $workflowItem;
    }

    /**
     * @return object
     */
    public function getCheckoutEntity()
    {
        return $this->checkoutEntity;
    }

    /**
     * @param object $checkoutEntity
     */
    public function setCheckoutEntity($checkoutEntity)
    {
        $this->checkoutEntity = $checkoutEntity;
    }

    /**
     * @return WorkflowItem
     */
    public function getWorkflowItem()
    {
        return $this->workflowItem;
    }
}

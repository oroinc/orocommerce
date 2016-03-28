<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\EventListener;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEvent;

class CheckoutEntityListener
{
    /**
     * @var WorkflowManager
     */
    protected $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param CheckoutEvent $event
     * @return AlternativeCheckout
     */
    public function onEntityCreate(CheckoutEvent $event)
    {
        return new AlternativeCheckout();
    }
}

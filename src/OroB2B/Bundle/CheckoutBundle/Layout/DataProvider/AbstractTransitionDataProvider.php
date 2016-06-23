<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use Oro\Component\Layout\AbstractServerRenderDataProvider;

//use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Model\TransitionData;

abstract class AbstractTransitionDataProvider extends AbstractServerRenderDataProvider
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
     * @param Transition $transition
     * @param WorkflowItem $workflowItem
     * @return TransitionData[]|null
     */
    protected function getTransitionData(Transition $transition, WorkflowItem $workflowItem)
    {
        $errors = new ArrayCollection();
        $isAllowed = $this->workflowManager->isTransitionAvailable($workflowItem, $transition, $errors);
        if ($isAllowed || !$transition->isUnavailableHidden()) {
            return new TransitionData($transition, $isAllowed, $errors);
        }

        return null;
    }

//    /**
//     * @param CheckoutInterface $checkout
//     * @return WorkflowItem
//     */
//    protected function getWorkflowItem(CheckoutInterface $checkout)
//    {
//        return $this->workflowManager->getWorkflowItemByGroup($checkout, 'checkout');
//    }
}

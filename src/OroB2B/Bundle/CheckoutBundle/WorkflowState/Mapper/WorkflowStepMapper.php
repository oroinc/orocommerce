<?php

namespace OroB2B\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAwareManager;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;

class WorkflowStepMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'workflow_step';

    /** @var WorkflowAwareManager */
    protected $workflowAwareManager;

    /**
     * @param WorkflowAwareManager $workflowAwareManager
     */
    public function __construct(WorkflowAwareManager $workflowAwareManager)
    {
        $this->workflowAwareManager = $workflowAwareManager;
    }

    /** {@inheritdoc} */
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::DATA_NAME;
    }

    /** {@inheritdoc} */
    public function getCurrentState($entity)
    {
        $workflowItem = $this->workflowAwareManager->getWorkflowItem($entity);

        if (!$workflowItem) {
            return null;
        }

        /** @var WorkflowTransitionRecord $lastTransitionRecord */
        $lastTransitionRecord = $workflowItem->getTransitionRecords()->last();
        if ($lastTransitionRecord) {
            $stepTo = $lastTransitionRecord->getStepTo();

            return $stepTo->getName();
        }


        return null;
    }

    /** {@inheritdoc} */
    public function isStatesEqual($entity, $state1, $state2)
    {
        return $state1 === $state2;
    }
}

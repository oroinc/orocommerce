<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAwareManager;

class WorkflowStepMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'workflow_step';

    /** @var WorkflowAwareManager */
    protected $workflowAwareManager;

    public function __construct(WorkflowAwareManager $workflowAwareManager)
    {
        $this->workflowAwareManager = $workflowAwareManager;
    }

    #[\Override]
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getName()
    {
        return self::DATA_NAME;
    }

    #[\Override]
    public function getCurrentState($entity)
    {
        $workflowItem = $this->workflowAwareManager->getWorkflowItem($entity);

        if (!$workflowItem) {
            return null;
        }

        if (!$workflowItem->getCurrentStep()) {
            return null;
        }

        return $workflowItem->getCurrentStep()->getName();
    }

    #[\Override]
    public function isStatesEqual($entity, $state1, $state2)
    {
        return $state1 === $state2;
    }
}

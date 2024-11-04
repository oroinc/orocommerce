<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderConfigurationProviderInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

/**
 * Sets the internal order status if it is not set yet
 * and there is not order processing workflow associated with the order.
 */
class OrderStatusListener
{
    public function __construct(
        private readonly OrderConfigurationProviderInterface $configurationProvider,
        private readonly ManagerRegistry $doctrine,
        private readonly WorkflowManager $workflowManager,
        private readonly string $orderStatusWorkflowGroup
    ) {
    }

    public function prePersist(Order $entity): void
    {
        if (null === $entity->getInternalStatus() && !$this->isOrderStatusWorkflowApplicable($entity)) {
            $this->setDefaultInternalStatus($entity);
        }
    }

    private function isOrderStatusWorkflowApplicable(Order $entity): bool
    {
        $workflow = $this->workflowManager->getAvailableWorkflowByRecordGroup(
            $entity,
            $this->orderStatusWorkflowGroup
        );

        return null !== $workflow;
    }

    private function setDefaultInternalStatus(Order $entity): void
    {
        $defaultInternalStatusId = $this->configurationProvider->getNewOrderInternalStatus($entity);
        if (!$defaultInternalStatusId) {
            return;
        }

        $entity->setInternalStatus(
            $this->doctrine->getRepository(EnumOption::class)->find($defaultInternalStatusId)
        );
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;

class CheckoutWorkflowListener
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $entityClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $entityClass
     */
    public function __construct(ManagerRegistry $registry, $entityClass)
    {
        $this->registry = $registry;
        $this->entityClass = $entityClass;
    }

    /**
     * @param WorkflowChangesEvent $event
     */
    public function onDeactivationWorkflowDefinition(WorkflowChangesEvent $event)
    {
        if ($event->getDefinition() && $event->getDefinition()->getRelatedEntity() === $this->entityClass) {
            $this->getRepository()->deleteWithoutWorkflowItem();
        }
    }

    /**
     * @return CheckoutRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass($this->entityClass)->getRepository($this->entityClass);
    }
}

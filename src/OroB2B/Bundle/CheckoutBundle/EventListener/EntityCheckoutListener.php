<?php

namespace OroB2B\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;

class EntityCheckoutListener
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var string
     */
    private $checkoutWorkflowStateClassName;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string $checkoutWorkflowStateClassName
     */
    public function __construct(ManagerRegistry $managerRegistry, $checkoutWorkflowStateClassName)
    {
        $this->managerRegistry = $managerRegistry;
        $this->checkoutWorkflowStateClassName = $checkoutWorkflowStateClassName;
    }

    /**
     * @return CheckoutWorkflowStateRepository
     */
    private function getRepository()
    {
        return $this->managerRegistry->getRepository($this->checkoutWorkflowStateClassName);
    }

    /**
     * @param Checkout $entity
     */
    public function preRemove(Checkout $entity)
    {
        $this->getRepository()->deleteEntityStates(
            $entity->getId(),
            ClassUtils::getClass($entity)
        );
    }
}

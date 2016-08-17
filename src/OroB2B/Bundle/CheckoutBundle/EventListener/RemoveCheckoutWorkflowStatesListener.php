<?php

namespace OroB2B\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;

class RemoveCheckoutWorkflowStatesListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var string
     */
    private $checkoutWorkflowStateClassName;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $checkoutWorkflowStateClassName
     */
    public function __construct(DoctrineHelper $doctrineHelper, $checkoutWorkflowStateClassName)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->checkoutWorkflowStateClassName = $checkoutWorkflowStateClassName;
    }

    /**
     * @param Checkout $entity
     */
    public function preRemove(Checkout $entity)
    {
        /** @var CheckoutWorkflowStateRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass($this->checkoutWorkflowStateClassName);

        $repository->deleteEntityStates(
            $entity->getId(),
            Checkout::class
        );
    }
}

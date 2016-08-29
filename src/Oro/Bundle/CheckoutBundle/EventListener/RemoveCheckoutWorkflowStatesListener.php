<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;

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

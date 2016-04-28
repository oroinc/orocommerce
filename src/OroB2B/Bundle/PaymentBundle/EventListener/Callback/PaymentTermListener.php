<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTermListener
{
    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /**
     * @param PropertyAccessor $propertyAccessor
     * @param DoctrineHelper $doctrineHelper
     * @param PaymentTermProvider $paymentTermProvider
     */
    public function __construct(
        PropertyAccessor $propertyAccessor,
        DoctrineHelper $doctrineHelper,
        PaymentTermProvider $paymentTermProvider
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrineHelper = $doctrineHelper;
        $this->paymentTermProvider = $paymentTermProvider;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onReturn(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();
        if (!$paymentTransaction->isSuccessful()) {
            return;
        }

        $entity = $this->doctrineHelper->getEntity(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity) {
            return;
        }

        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        if (!$paymentTerm) {
            return;
        }

        try {
            $this->propertyAccessor->setValue($entity, 'paymentTerm', $paymentTerm);
            $this->doctrineHelper->getEntityManager($entity)->flush($entity);
        } catch (NoSuchPropertyException $e) {
        }
    }
}

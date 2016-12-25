<?php

namespace Oro\Bundle\PaymentTermBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class PaymentTerm implements PaymentMethodInterface
{
    const TYPE = 'payment_term';

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param PropertyAccessor $propertyAccessor
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        PropertyAccessor $propertyAccessor,
        DoctrineHelper $doctrineHelper
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrineHelper = $doctrineHelper;
    }

    /** {@inheritdoc} */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity) {
            return [];
        }

        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        if (!$paymentTerm) {
            return [];
        }

        try {
            $this->propertyAccessor->setValue($entity, 'paymentTerm', $paymentTerm);
            $this->doctrineHelper->getEntityManager($entity)->flush($entity);
        } catch (NoSuchPropertyException $e) {
            return [];
        }

        $paymentTransaction
            ->setSuccessful(true)
            ->setActive(false);

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        return (bool)$this->paymentTermProvider->getPaymentTerm($context->getCustomer());
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return self::TYPE;
    }

    /** {@inheritdoc} */
    public function supports($actionName)
    {
        return $actionName === self::PURCHASE;
    }
}

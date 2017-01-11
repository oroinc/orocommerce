<?php

namespace Oro\Bundle\PaymentTermBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerAwareTrait;

class PaymentTermMethodProvider implements PaymentMethodProviderInterface
{
    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var PaymentTermAssociationProvider */
    protected $paymentTermAssociationProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    use LoggerAwareTrait;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods()
    {
        $paymentMethod = new PaymentTerm(
            $this->paymentTermProvider,
            $this->paymentTermAssociationProvider,
            $this->doctrineHelper
        );
        $paymentMethod->setLogger($this->logger);
        return [$this->getType() => $paymentMethod];
    }

    /**
     * @return PaymentMethodInterface
     */
    public function getPaymentMethod($identifier)
    {
        return $this->getPaymentMethods()[$identifier];
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethod($identifier)
    {
        return $this->getType() === $identifier;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return PaymentTerm::TYPE;
    }
}

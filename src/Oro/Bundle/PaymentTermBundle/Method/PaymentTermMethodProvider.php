<?php

namespace Oro\Bundle\PaymentTermBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Psr\Log\LoggerAwareTrait;

class PaymentTermMethodProvider implements PaymentMethodProviderInterface
{
    use LoggerAwareTrait;

    /**
     * @var PaymentTermProvider
     */
    protected $paymentTermProvider;

    /**
     * @var PaymentTermAssociationProvider
     */
    protected $paymentTermAssociationProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

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
        return [$paymentMethod->getIdentifier() => $paymentMethod];
    }

    /**
     * @param string $identifier
     * @return PaymentMethodInterface
     */
    public function getPaymentMethod($identifier)
    {
        if ($this->hasPaymentMethod($identifier)) {
            return $this->getPaymentMethods()[$identifier];
        }
        return null;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethod($identifier)
    {
        $paymentMethods = $this->getPaymentMethods();

        return isset($paymentMethods[$identifier]);
    }
}

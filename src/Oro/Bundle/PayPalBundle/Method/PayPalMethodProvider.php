<?php

namespace Oro\Bundle\PayPalBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;

class PayPalMethodProvider implements PaymentMethodProviderInterface
{
    /** @var string */
    protected $providerType;

    /** @var Gateway */
    protected $gateway;

    /** @var RouterInterface */
    protected $router;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var PaymentConfigInterface */
    protected $config;

    /** @var ExtractOptionsProvider */
    protected $optionsProvider;

    /** @var SurchargeProvider */
    protected $surchargeProvider;


    /**
     * @param string $providerType
     * @param Gateway $gateway
     * @param PayflowGatewayConfigInterface|PayflowExpressCheckoutConfigInterface $config
     * @param RouterInterface $router
     * @param DoctrineHelper|null $doctrineHelper
     * @param ExtractOptionsProvider|null $optionsProvider
     * @param SurchargeProvider|null $surchargeProvider
     */
    public function __construct(
        $providerType,
        Gateway $gateway,
        $config,
        RouterInterface $router,
        DoctrineHelper $doctrineHelper = null,
        ExtractOptionsProvider $optionsProvider = null,
        SurchargeProvider $surchargeProvider = null
    ) {
        $this->providerType = $providerType;
        $this->gateway = $gateway;
        $this->config = $config;
        $this->router = $router;
        $this->doctrineHelper = $doctrineHelper;
        $this->optionsProvider = $optionsProvider;
        $this->surchargeProvider = $surchargeProvider;
    }

    /**
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods()
    {
        switch ($this->providerType) {
            case 'payflow_gateway':
                $paymentMethod = new PayflowGateway(
                    $this->gateway,
                    $this->config,
                    $this->router
                );
                break;
            case 'paypal_payments_pro':
                $paymentMethod = new PayPalPaymentsPro(
                    $this->gateway,
                    $this->config,
                    $this->router
                );
                break;
            case 'payflow_express_checkout':
                $paymentMethod = new PayflowExpressCheckout(
                    $this->gateway,
                    $this->config,
                    $this->router,
                    $this->doctrineHelper,
                    $this->optionsProvider,
                    $this->surchargeProvider
                );
                break;
            case 'paypal_payments_pro_express_checkout':
                $paymentMethod = new PayPalPaymentsProExpressCheckout(
                    $this->gateway,
                    $this->config,
                    $this->router,
                    $this->doctrineHelper,
                    $this->optionsProvider,
                    $this->surchargeProvider
                );
                break;
        }

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
        return $this->providerType;
    }
}

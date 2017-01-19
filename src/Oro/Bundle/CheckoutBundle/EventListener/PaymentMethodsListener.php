<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentMethodsConfigsRulesProviderInterface;

class PaymentMethodsListener extends AbstractMethodsListener
{
    /**
     * @var PaymentMethodsConfigsRulesProviderInterface
     */
    private $paymentProvider;

    /**
     * @var CheckoutPaymentContextFactory
     */
    private $contextFactory;

    /**
     * @param OrderAddressProvider $addressProvider
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param OrderAddressManager $orderAddressManager
     * @param PaymentMethodsConfigsRulesProviderInterface $paymentProvider
     * @param CheckoutPaymentContextFactory $contextFactory
     */
    public function __construct(
        OrderAddressProvider $addressProvider,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        OrderAddressManager $orderAddressManager,
        PaymentMethodsConfigsRulesProviderInterface $paymentProvider,
        CheckoutPaymentContextFactory $contextFactory
    ) {
        parent::__construct($addressProvider, $orderAddressSecurityProvider, $orderAddressManager);

        $this->paymentProvider = $paymentProvider;
        $this->contextFactory = $contextFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasMethodsConfigsForAddress(Checkout $checkout, OrderAddress $address = null)
    {
        $checkout->setBillingAddress($address);
        $paymentContext = $this->contextFactory->create($checkout);
        $paymentMethodsConfigs = $this->paymentProvider->getFilteredPaymentMethodsConfigs($paymentContext);
        return (bool) count($paymentMethodsConfigs);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAddressType()
    {
        return AddressType::TYPE_BILLING;
    }

    /**
     * {@inheritdoc}
     */
    protected function getError()
    {
        return 'oro.checkout.frontend.checkout.cannot_create_order_no_payment_methods_available';
    }
}

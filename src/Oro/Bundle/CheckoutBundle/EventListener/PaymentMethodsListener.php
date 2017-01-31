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
     * @var OrderAddressProvider
     */
    private $addressProvider;

    /**
     * @var OrderAddressSecurityProvider
     */
    private $orderAddressSecurityProvider;

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
        parent::__construct($orderAddressManager);

        $this->addressProvider = $addressProvider;
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
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
        $paymentMethodsConfigs = $this->paymentProvider
            ->getFilteredPaymentMethodsConfigsRegardlessDestination($paymentContext);

        return count($paymentMethodsConfigs) > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function getError()
    {
        return 'oro.payment.methods.no_method';
    }

    /**
     * {@inheritdoc}
     */
    protected function isManualEditGranted()
    {
        return $this->orderAddressSecurityProvider->isManualEditGranted(AddressType::TYPE_BILLING);
    }

    /**
     * {@inheritdoc}
     */
    protected function getApplicableAddresses(Checkout $checkout)
    {
        return array_merge(
            $this->addressProvider->getCustomerAddresses($checkout->getCustomer(), AddressType::TYPE_BILLING),
            $this->addressProvider->getCustomerUserAddresses($checkout->getCustomerUser(), AddressType::TYPE_BILLING)
        );
    }
}

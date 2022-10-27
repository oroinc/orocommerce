<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

/**
 * Checks if there are available payment methods when checkout starts.
 */
class PaymentMethodsListener extends AbstractMethodsListener
{
    /**
     * @var MethodsConfigsRulesByContextProviderInterface
     */
    private $paymentProvider;

    /**
     * @var CheckoutPaymentContextProvider
     */
    private $checkoutPaymentContextProvider;

    /**
     * @var OrderAddressProvider
     */
    private $addressProvider;

    /**
     * @var OrderAddressSecurityProvider
     */
    private $orderAddressSecurityProvider;

    public function __construct(
        OrderAddressProvider $addressProvider,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        OrderAddressManager $orderAddressManager,
        MethodsConfigsRulesByContextProviderInterface $paymentProvider,
        CheckoutPaymentContextProvider $checkoutPaymentContextProvider
    ) {
        parent::__construct($orderAddressManager);

        $this->addressProvider = $addressProvider;
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->paymentProvider = $paymentProvider;
        $this->checkoutPaymentContextProvider = $checkoutPaymentContextProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function hasMethodsConfigsForAddress(Checkout $checkout, OrderAddress $address = null)
    {
        $checkout->setBillingAddress($address);
        $paymentContext = $this->checkoutPaymentContextProvider->getContext($checkout);
        $paymentMethodsConfigs = $this->paymentProvider->getPaymentMethodsConfigsRules($paymentContext);

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

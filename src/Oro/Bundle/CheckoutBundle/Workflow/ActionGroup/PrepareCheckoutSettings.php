<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\AddressActionsInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Prepare checkout settings by a given source.
 */
class PrepareCheckoutSettings implements PrepareCheckoutSettingsInterface
{
    public function __construct(
        private AddressActionsInterface $addressActions,
        private PaymentTransactionProvider $paymentTransactionProvider
    ) {
    }

    #[\Override]
    public function execute(CheckoutSourceEntityInterface $source): array
    {
        $settings = [];
        if (method_exists($source, 'getBillingAddress') && $source->getBillingAddress()) {
            $settings['billing_address'] = $this->addressActions
                ->duplicateOrderAddress($source->getBillingAddress());
        }

        if (method_exists($source, 'getShippingAddress') && $source->getShippingAddress()) {
            $settings['shipping_address'] = $this->addressActions
                ->duplicateOrderAddress($source->getShippingAddress());
        }

        if ($source instanceof PreConfiguredShippingMethodConfigurationInterface
            && $source->getShippingMethod()
            && $source->getShippingMethodType()
        ) {
            $settings['shipping_method'] = $source->getShippingMethod();
            $settings['shipping_method_type'] = $source->getShippingMethodType();
        }

        $paymentMethods = $this->paymentTransactionProvider->getPaymentMethods($source);
        if (count($paymentMethods) > 0) {
            $settings['payment_method'] = reset($paymentMethods);
        }

        return $settings;
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingGroupMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets a default shipping method if it is not set yet.
 */
class SetDefaultShippingMethod implements ProcessorInterface
{
    public function __construct(
        private readonly ConfigProvider $configProvider,
        private readonly DefaultShippingMethodSetterInterface $defaultShippingMethodSetter,
        private readonly DefaultMultiShippingMethodSetterInterface $defaultMultiShippingMethodSetter,
        private readonly DefaultMultiShippingGroupMethodSetterInterface $defaultMultiShippingGroupMethodSetter
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var Checkout $checkout */
        $checkout = $context->getData();
        if ($this->configProvider->isShippingSelectionByLineItemEnabled()) {
            $this->defaultMultiShippingMethodSetter->setDefaultShippingMethods($checkout, null, true);
        } elseif ($this->configProvider->isLineItemsGroupingEnabled()) {
            $this->defaultMultiShippingGroupMethodSetter->setDefaultShippingMethods($checkout, null, true);
        } else {
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }
    }
}

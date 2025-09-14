<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitFormattedProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that minimum and maximum order amounts are met
 * when creating order via the storefront API.
 */
class ValidateOrderLimitAmounts implements ProcessorInterface
{
    public function __construct(
        private readonly OrderLimitProviderInterface $orderLimitProvider,
        private readonly OrderLimitFormattedProviderInterface $orderLimitFormattedProvider,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var Order $order */
        $order = $context->getData();

        if (!$this->orderLimitProvider->isMinimumOrderAmountMet($order)) {
            FormUtil::addNamedFormError(
                $context->getForm(),
                'order limit amounts constraint',
                $this->translator->trans('oro.order.order_limits.minimum_order_amount', [
                    '%amount%' => $this->orderLimitFormattedProvider->getMinimumOrderAmountFormatted(),
                    '%difference%' =>
                        $this->orderLimitFormattedProvider->getMinimumOrderAmountDifferenceFormatted($order)
                ], 'validators')
            );
        }

        if (!$this->orderLimitProvider->isMaximumOrderAmountMet($order)) {
            FormUtil::addNamedFormError(
                $context->getForm(),
                'order limit amounts constraint',
                $this->translator->trans('oro.order.order_limits.maximum_order_amount', [
                    '%amount%' => $this->orderLimitFormattedProvider->getMaximumOrderAmountFormatted(),
                    '%difference%' =>
                        $this->orderLimitFormattedProvider->getMaximumOrderAmountDifferenceFormatted($order)
                ], 'validators')
            );
        }
    }
}

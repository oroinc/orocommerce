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
 * Validates that minimum and maximum order amounts are met when creating order via frontend API
 * It works as a processor because this validation should run after
 * oro_order.api.fill_order_line_item_price is executed and line item prices are filled
 */
class OrderLimitAmountsValidationProcessor implements ProcessorInterface
{
    private string $minimumOrderAmountMessage = 'oro.order.order_limits.minimum_order_amount';
    private string $maximumOrderAmountMessage = 'oro.order.order_limits.maximum_order_amount';

    public function __construct(
        private OrderLimitProviderInterface $orderLimitProvider,
        private OrderLimitFormattedProviderInterface $orderLimitFormattedProvider,
        private TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $order = $context->getData();

        if (!$order instanceof Order) {
            return;
        }

        if (!$this->orderLimitProvider->isMinimumOrderAmountMet($order)) {
            FormUtil::addNamedFormError(
                $context->getForm(),
                'order limit amounts constraint',
                $this->translator->trans($this->minimumOrderAmountMessage, [
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
                $this->translator->trans($this->maximumOrderAmountMessage, [
                    '%amount%' => $this->orderLimitFormattedProvider->getMaximumOrderAmountFormatted(),
                    '%difference%' =>
                        $this->orderLimitFormattedProvider->getMaximumOrderAmountDifferenceFormatted($order)
                ], 'validators')
            );
        }
    }
}

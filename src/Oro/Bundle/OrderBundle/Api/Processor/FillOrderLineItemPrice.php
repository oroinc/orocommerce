<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Calculates the price ("value" and "currency" fields) for {@see OrderLineItem},
 * compares the calculated price with a price stored in the context (it is a price submitted by a user)
 * and sets the calculated value to the {@see OrderLineItem} if the prices are equal.
 */
class FillOrderLineItemPrice implements ProcessorInterface
{
    private const string SUBMITTED_PRICE = 'order_line_item_submitted_price';

    public function __construct(
        private readonly ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        private readonly ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        private readonly TranslatorInterface $translator
    ) {
    }

    public static function setSubmittedPrice(CustomizeFormDataContext $context, mixed $value, mixed $currency): void
    {
        $context->set(self::SUBMITTED_PRICE, ['value' => $value, 'currency' => $currency]);
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        /** @var OrderLineItem $lineItem */
        $lineItem = $context->getData();
        if (!$this->isPriceCanBeCalculated($lineItem)) {
            return;
        }

        $productPrice = $this->getProductPrice($lineItem);
        if (null === $productPrice) {
            FormUtil::addNamedFormError(
                $context->findFormField('value'),
                'price not found constraint',
                $this->translator->trans('oro.order.orderlineitem.product_price.blank', [], 'validators')
            );
        } elseif ($this->validateSubmittedPrice($productPrice, $lineItem, $context)) {
            $lineItem->setValue($productPrice->getValue());
        }
    }

    private function isPriceCanBeCalculated(OrderLineItem $lineItem): bool
    {
        return
            null !== $lineItem->getProduct()
            && null !== $lineItem->getProductUnit()
            && null !== $lineItem->getQuantity();
    }

    private function getProductPrice(OrderLineItem $lineItem): ?Price
    {
        $order = $lineItem->getOrder();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->priceScopeCriteriaFactory->createByContext($order),
            $order->getCurrency()
        );
        if (!isset($productLineItemPrices[0])) {
            return null;
        }

        return $productLineItemPrices[0]->getPrice();
    }

    private function validateSubmittedPrice(
        Price $productPrice,
        OrderLineItem $lineItem,
        CustomizeFormDataContext $context
    ): bool {
        $submittedPrice = $context->get(self::SUBMITTED_PRICE);
        if (!$submittedPrice) {
            return true;
        }

        $isValid = true;

        $submittedPriceValue = $submittedPrice['value'];
        $calculatedPriceValue = $productPrice->getValue();
        if (null !== $submittedPriceValue && $submittedPriceValue !== $calculatedPriceValue) {
            $isValid = false;
            $lineItem->setValue($submittedPriceValue);
            FormUtil::addNamedFormError(
                $context->findFormField('value'),
                'price match constraint',
                $this->translator->trans(
                    'oro.order.orderlineitem.product_price.not_match',
                    ['{{ expected_value }}' => $calculatedPriceValue],
                    'validators'
                )
            );
        }

        $submittedPriceCurrency = $submittedPrice['currency'];
        $calculatedPriceCurrency = $productPrice->getCurrency();
        if ($submittedPriceCurrency && $submittedPriceCurrency !== $calculatedPriceCurrency) {
            $isValid = false;
            $lineItem->setValue($submittedPriceValue);
            FormUtil::addNamedFormError(
                $context->findFormField('currency'),
                'currency match constraint',
                $this->translator->trans(
                    'oro.order.orderlineitem.currency.not_match',
                    ['{{ expected_value }}' => $calculatedPriceCurrency],
                    'validators'
                )
            );
        }

        return $isValid;
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Calculates the price ("value" and "currency" fields) for {@see OrderProductKitItemLineItem},
 * compares the calculated price with a price stored in the context (it is a price submitted by a user)
 * and sets the calculated value to the {@see OrderProductKitItemLineItem} if the prices are equal.
 */
class FillOrderProductKitItemLineItemPrice implements ProcessorInterface
{
    private const string SUBMITTED_PRICE = 'order_product_kit_item_line_item_submitted_price';

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

        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $context->getData();
        if (!$this->isPriceCanBeCalculated($kitItemLineItem)) {
            return;
        }

        $productPrice = $this->getProductPrice($kitItemLineItem);
        if (null === $productPrice) {
            FormUtil::addNamedFormError(
                $context->findFormField('value'),
                'price not found constraint',
                $this->translator->trans('oro.order.orderproductkititemlineitem.product_price.blank', [], 'validators')
            );
        } elseif ($this->validateSubmittedPrice($productPrice, $kitItemLineItem, $context)) {
            $kitItemLineItem->setCurrency($productPrice->getCurrency());
            $kitItemLineItem->setValue($productPrice->getValue());
        }
    }

    private function isPriceCanBeCalculated(OrderProductKitItemLineItem $kitItemLineItem): bool
    {
        $lineItem = $kitItemLineItem->getLineItem();

        return
            null !== $lineItem
            && null !== $kitItemLineItem->getProduct()
            && null !== $kitItemLineItem->getProductUnit()
            && null !== $kitItemLineItem->getQuantity()
            && null !== $lineItem->getProduct()
            && null !== $lineItem->getProductUnit()
            && null !== $lineItem->getQuantity();
    }

    private function getProductPrice(OrderProductKitItemLineItem $kitItemLineItem): ?Price
    {
        $lineItem = $kitItemLineItem->getLineItem();
        $order = $lineItem->getOrder();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->priceScopeCriteriaFactory->createByContext($order),
            $order->getCurrency()
        );
        if (!isset($productLineItemPrices[0])) {
            return null;
        }

        if (!$productLineItemPrices[0] instanceof ProductKitLineItemPrice) {
            return null;
        }

        return $productLineItemPrices[0]->getKitItemLineItemPrice($kitItemLineItem)?->getPrice();
    }

    private function validateSubmittedPrice(
        Price $productPrice,
        OrderProductKitItemLineItem $kitItemLineItem,
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
            $kitItemLineItem->setValue($submittedPriceValue);
            FormUtil::addNamedFormError(
                $context->findFormField('value'),
                'price match constraint',
                $this->translator->trans(
                    'oro.order.orderproductkititemlineitem.product_price.not_match',
                    ['{{ expected_value }}' => $calculatedPriceValue],
                    'validators'
                )
            );
        }

        $submittedPriceCurrency = $submittedPrice['currency'];
        $calculatedPriceCurrency = $productPrice->getCurrency();
        if ($submittedPriceCurrency && $submittedPriceCurrency !== $calculatedPriceCurrency) {
            $isValid = false;
            $kitItemLineItem->setValue($submittedPriceValue);
            FormUtil::addNamedFormError(
                $context->findFormField('currency'),
                'currency match constraint',
                $this->translator->trans(
                    'oro.order.orderproductkititemlineitem.currency.not_match',
                    ['{{ expected_value }}' => $calculatedPriceCurrency],
                    'validators'
                )
            );
        }

        return $isValid;
    }
}

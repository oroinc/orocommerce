<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Calculates the price ("value" and "currency" fields) for {@see OrderProductKitItemLineItem},
 * compares the calculated price with a price stored in the context (it is a price submitted by a user)
 * and sets the calculated value to the {@see OrderProductKitItemLineItem} if the prices are equal.
 */
class FillOrderProductKitItemLineItemPrice extends AbstractFillLineItemPrice
{
    public function __construct(
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        TranslatorInterface $translator
    ) {
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->translator = $translator;
    }

    protected function getProductLineItemPrice(CustomizeFormDataContext $context): ?ProductLineItemPrice
    {
        $productLineItemPrice = parent::getProductLineItemPrice($context);
        if ($productLineItemPrice instanceof ProductKitLineItemPrice) {
            $kitItemLineItem = $context->getData();

            return $productLineItemPrice->getKitItemLineItemPrice($kitItemLineItem);
        }

        return null;
    }

    protected function isPriceCanBeCalculated(OrderLineItem|OrderProductKitItemLineItem $lineItem): bool
    {
        return parent::isPriceCanBeCalculated($lineItem->getLineItem())
            && parent::isPriceCanBeCalculated($lineItem);
    }

    protected function getOrderLineItem(CustomizeFormDataContext $context): OrderLineItem
    {
        return $context->getData()->getLineItem();
    }

    protected function getPriceNotFoundErrorMessage(): string
    {
        return $this->translator->trans('oro.order.orderproductkititemlineitem.product_price.blank', [], 'validators');
    }

    protected function getPriceNotMatchErrorMessage($expectedValue): string
    {
        return $this->translator->trans(
            'oro.order.orderproductkititemlineitem.product_price.not_match',
            ['{{ expected_value }}' => $expectedValue],
            'validators'
        );
    }

    protected function getCurrencyNotMatchErrorMessage($expectedValue): string
    {
        return $this->translator->trans(
            'oro.order.orderproductkititemlineitem.currency.not_match',
            ['{{ expected_value }}' => $expectedValue],
            'validators'
        );
    }

    protected function getSubmittedPriceKey(): string
    {
        return RememberOrderProductKitItemLineItemPrice::SUBMITTED_PRICE;
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Calculates the price ("price" and "currency" fields) for an order line item,
 * compares the calculated price with a price stored in the context (it is a price submitted by an user)
 * and sets the calculated value to the order line item if the prices are equal.
 */
class FillOrderLineItemPrice implements ProcessorInterface
{
    private MatchingPriceProvider $priceProvider;
    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;
    private TranslatorInterface $translator;

    public function __construct(
        MatchingPriceProvider $priceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        TranslatorInterface $translator
    ) {
        $this->priceProvider = $priceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
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

        $calculatedPrice = $this->calculatePrice($lineItem);
        if (null === $calculatedPrice) {
            FormUtil::addNamedFormError(
                $context->findFormField('value'),
                'price not found constraint',
                $this->translator->trans('oro.order.orderlineitem.product_price.blank', [], 'validators')
            );
        } elseif ($this->validateSubmittedPrice($calculatedPrice, $lineItem, $context)) {
            $lineItem->setCurrency($calculatedPrice['currency']);
            $lineItem->setValue($calculatedPrice['value']);
        }
    }

    private function isPriceCanBeCalculated(OrderLineItem $lineItem): bool
    {
        return
            null !== $lineItem->getProduct()
            && null !== $lineItem->getProductUnit()
            && null !== $lineItem->getQuantity();
    }

    private function calculatePrice(OrderLineItem $lineItem): ?array
    {
        $product = $lineItem->getProduct();
        $lineItemData = [
            'product'  => $product->getId(),
            'unit'     => $lineItem->getProductUnit()->getCode(),
            'qty'      => $lineItem->getQuantity(),
            'currency' => $lineItem->getOrder()->getCurrency()
        ];

        $prices = $this->priceProvider->getMatchingPrices(
            [$lineItemData],
            $this->priceScopeCriteriaFactory->createByContext($lineItem->getOrder())
        );
        if (!$prices) {
            return null;
        }

        return reset($prices);
    }

    private function validateSubmittedPrice(
        array $calculatedPrice,
        OrderLineItem $lineItem,
        CustomizeFormDataContext $context
    ): bool {
        $submittedPrice = $context->get(RememberOrderLineItemPrice::SUBMITTED_PRICE);
        if (!$submittedPrice) {
            return true;
        }

        $isValid = true;

        $submittedPriceValue = $submittedPrice['value'];
        $normalizedSubmittedPriceValue = null;
        if (null !== $submittedPriceValue) {
            $normalizedSubmittedPriceValue = $this->normalizePriceValue($submittedPriceValue);
        }
        $calculatedPriceValue = $calculatedPrice['value'];
        if (null !== $submittedPriceValue && $normalizedSubmittedPriceValue !== $calculatedPriceValue) {
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
        $calculatedPriceCurrency = $calculatedPrice['currency'];
        if (null !== $submittedPriceCurrency && $submittedPriceCurrency !== $calculatedPriceCurrency) {
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

    private function normalizePriceValue($value): ?float
    {
        try {
            return BigDecimal::of($value)->toFloat();
        } catch (MathException $e) {
            return null;
        }
    }
}

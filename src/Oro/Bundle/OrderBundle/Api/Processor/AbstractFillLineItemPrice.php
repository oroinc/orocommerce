<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Calculates the price ("value" and "currency" fields) for an order line item,
 * compares the calculated price with a price stored in the context (it is a price submitted by a user)
 * and sets the calculated value to the order line item if the prices are equal.
 */
abstract class AbstractFillLineItemPrice implements ProcessorInterface
{
    protected MatchingPriceProvider $priceProvider;

    protected ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    protected TranslatorInterface $translator;

    protected ?ProductLineItemPriceProviderInterface $productLineItemPriceProvider = null;

    public function __construct(
        MatchingPriceProvider $priceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        TranslatorInterface $translator
    ) {
        $this->priceProvider = $priceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->translator = $translator;
    }

    public function setProductLineItemPriceProvider(
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider
    ): void {
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
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

        /** @var OrderLineItem|OrderProductKitItemLineItem $lineItem */
        $lineItem = $context->getData();
        if (!$this->isPriceCanBeCalculated($lineItem)) {
            return;
        }

        $productLineItemPrice = $this->productLineItemPriceProvider !== null
            ? $this->getProductLineItemPrice($context)
            : $this->calculatePrice($lineItem);

        if (null === $productLineItemPrice) {
            FormUtil::addNamedFormError(
                $context->findFormField('value'),
                'price not found constraint',
                $this->getPriceNotFoundErrorMessage()
            );
        } elseif ($this->validateSubmittedPrice($productLineItemPrice, $lineItem, $context)) {
            $lineItem->setCurrency(
                is_array($productLineItemPrice)
                    ? $productLineItemPrice['currency']
                    : $productLineItemPrice->getPrice()->getCurrency()
            );
            $lineItem->setValue(
                is_array($productLineItemPrice)
                ? $productLineItemPrice['value']
                : $productLineItemPrice->getPrice()->getValue()
            );
        }
    }

    protected function isPriceCanBeCalculated(OrderLineItem|OrderProductKitItemLineItem $lineItem): bool
    {
        return
            null !== $lineItem->getProduct()
            && null !== $lineItem->getProductUnit()
            && null !== $lineItem->getQuantity();
    }

    protected function getProductLineItemPrice(CustomizeFormDataContext $context): ?ProductLineItemPrice
    {
        $lineItem = $this->getOrderLineItem($context);
        $sharedProductLineItemPrices = $context->getSharedData()->get('product_line_item_prices') ?? [];
        $lineItemHash = spl_object_hash($lineItem);
        if (!isset($sharedProductLineItemPrices[$lineItemHash])) {
            $order = $lineItem->getOrder();
            $sharedProductLineItemPrices += $this->productLineItemPriceProvider->getProductLineItemsPrices(
                [$lineItemHash => $lineItem],
                $this->priceScopeCriteriaFactory->createByContext($order),
                $order->getCurrency()
            );
        }

        return $sharedProductLineItemPrices[$lineItemHash] ?? null;
    }

    protected function calculatePrice(OrderLineItem $lineItem): ?array
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
        ProductLineItemPrice|array $productLineItemPrice,
        OrderLineItem|OrderProductKitItemLineItem $lineItem,
        CustomizeFormDataContext $context
    ): bool {
        $submittedPrice = $context->get($this->getSubmittedPriceKey());
        if (!$submittedPrice) {
            return true;
        }

        $isValid = true;

        $submittedPriceValue = $submittedPrice['value'];
        $normalizedSubmittedPriceValue = null;
        if (null !== $submittedPriceValue) {
            $normalizedSubmittedPriceValue = $this->normalizePriceValue($submittedPriceValue);
        }
        $calculatedPriceValue = is_array($productLineItemPrice)
            ? $productLineItemPrice['value']
            : $productLineItemPrice->getPrice()->getValue();
        if (null !== $submittedPriceValue && $normalizedSubmittedPriceValue !== $calculatedPriceValue) {
            $isValid = false;
            $lineItem->setValue($submittedPriceValue);
            FormUtil::addNamedFormError(
                $context->findFormField('value'),
                'price match constraint',
                $this->getPriceNotMatchErrorMessage($calculatedPriceValue)
            );
        }

        $submittedPriceCurrency = $submittedPrice['currency'];
        $calculatedPriceCurrency = is_array($productLineItemPrice)
            ? $productLineItemPrice['currency']
            : $productLineItemPrice->getPrice()->getCurrency();
        if (null !== $submittedPriceCurrency && $submittedPriceCurrency !== $calculatedPriceCurrency) {
            $isValid = false;
            $lineItem->setValue($submittedPriceValue);
            FormUtil::addNamedFormError(
                $context->findFormField('currency'),
                'currency match constraint',
                $this->getCurrencyNotMatchErrorMessage($calculatedPriceCurrency)
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

    abstract protected function getOrderLineItem(CustomizeFormDataContext $context): OrderLineItem;

    abstract protected function getPriceNotFoundErrorMessage(): string;

    abstract protected function getPriceNotMatchErrorMessage($expectedValue): string;

    abstract protected function getCurrencyNotMatchErrorMessage($expectedValue): string;

    abstract protected function getSubmittedPriceKey(): string;
}

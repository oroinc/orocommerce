<?php

namespace Oro\Bundle\PromotionBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\PromotionBundle\Api\OrderLineItemDiscountProvider;
use Oro\Bundle\TaxBundle\Api\OrderLineItemTaxesProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for the following fields for OrderLineItem entity:
 * * rowTotalDiscountAmount
 * * rowTotalAfterDiscount
 * * rowTotalAfterDiscountIncludingTax
 * * rowTotalAfterDiscountExcludingTax
 */
class ComputeOrderLineItemDiscounts implements ProcessorInterface
{
    private const ROW_TOTAL_DISCOUNT_AMOUNT = 'rowTotalDiscountAmount';
    private const ROW_TOTAL_AFTER_DISCOUNT = 'rowTotalAfterDiscount';
    private const ROW_TOTAL_AFTER_DISCOUNT_INCLUDING_TAX = 'rowTotalAfterDiscountIncludingTax';
    private const ROW_TOTAL_AFTER_DISCOUNT_EXCLUDING_TAX = 'rowTotalAfterDiscountExcludingTax';
    private const FIELD_NAMES = [
        self::ROW_TOTAL_DISCOUNT_AMOUNT,
        self::ROW_TOTAL_AFTER_DISCOUNT,
        self::ROW_TOTAL_AFTER_DISCOUNT_INCLUDING_TAX,
        self::ROW_TOTAL_AFTER_DISCOUNT_EXCLUDING_TAX
    ];

    private OrderLineItemDiscountProvider $lineItemDiscountProvider;
    private OrderLineItemTaxesProvider $lineItemTaxesProvider;
    private ValueTransformer $valueTransformer;

    public function __construct(
        OrderLineItemDiscountProvider $lineItemDiscountProvider,
        OrderLineItemTaxesProvider $lineItemTaxesProvider,
        ValueTransformer $valueTransformer
    ) {
        $this->lineItemDiscountProvider = $lineItemDiscountProvider;
        $this->lineItemTaxesProvider = $lineItemTaxesProvider;
        $this->valueTransformer = $valueTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isAtLeastOneFieldRequestedForCollection(self::FIELD_NAMES, $data)) {
            return;
        }

        $lineItemIdFieldName = $context->getResultFieldName('id');
        if ($lineItemIdFieldName) {
            $context->setData($this->applyDiscounts($context, $data, $lineItemIdFieldName));
        }
    }

    private function applyDiscounts(
        CustomizeLoadedDataContext $context,
        array $data,
        string $lineItemIdFieldName
    ): array {
        $lineItemIds = $context->getIdentifierValues($data, $lineItemIdFieldName);
        $appliedDiscounts = $this->lineItemDiscountProvider->getDiscounts($context, $lineItemIds);
        $normalizationContext = $context->getNormalizationContext();
        foreach ($data as $key => $item) {
            $lineItemId = $item[$lineItemIdFieldName];
            $discountAmount = $this->getLineItemDiscount($appliedDiscounts, $lineItemId);
            if ($context->isFieldRequested(self::ROW_TOTAL_DISCOUNT_AMOUNT, $item)) {
                $data[$key][self::ROW_TOTAL_DISCOUNT_AMOUNT] = $this->valueTransformer->transformValue(
                    $discountAmount,
                    DataType::MONEY,
                    $normalizationContext
                );
            }
            if ($context->isFieldRequested(self::ROW_TOTAL_AFTER_DISCOUNT, $item)) {
                $data[$key][self::ROW_TOTAL_AFTER_DISCOUNT] = $this->valueTransformer->transformValue(
                    $this->getRowTotalAfterDiscount($context, $item, $discountAmount),
                    DataType::MONEY,
                    $normalizationContext
                );
            }
            if ($context->isFieldRequested(self::ROW_TOTAL_AFTER_DISCOUNT_INCLUDING_TAX, $item)) {
                $data[$key][self::ROW_TOTAL_AFTER_DISCOUNT_INCLUDING_TAX] = $this->valueTransformer->transformValue(
                    $this->getRowTotalAfterDiscountWithTax(
                        OrderLineItemTaxesProvider::ROW_TOTAL_INCLUDING_TAX,
                        $discountAmount,
                        $this->getLineItemTaxes($context, $lineItemIds, $lineItemId)
                    ),
                    DataType::MONEY,
                    $normalizationContext
                );
            }
            if ($context->isFieldRequested(self::ROW_TOTAL_AFTER_DISCOUNT_EXCLUDING_TAX, $item)) {
                $data[$key][self::ROW_TOTAL_AFTER_DISCOUNT_EXCLUDING_TAX] = $this->valueTransformer->transformValue(
                    $this->getRowTotalAfterDiscountWithTax(
                        OrderLineItemTaxesProvider::ROW_TOTAL_EXCLUDING_TAX,
                        $discountAmount,
                        $this->getLineItemTaxes($context, $lineItemIds, $lineItemId)
                    ),
                    DataType::MONEY,
                    $normalizationContext
                );
            }
        }

        return $data;
    }

    private function getRowTotalAfterDiscount(
        CustomizeLoadedDataContext $context,
        array $item,
        string|float $discountAmount
    ): string|float {
        $price = $context->getResultFieldValue('value', $item);
        $quantity = $context->getResultFieldValue('quantity', $item);
        $result = ($price * $quantity) - $discountAmount;
        if ($result < 0) {
            $result = 0.0;
        }

        return $result;
    }

    private function getRowTotalAfterDiscountWithTax(
        string $amountFieldName,
        string|float $discountAmount,
        array $lineItemTaxes
    ): string|float {
        $result = 0.0;
        $amount = $lineItemTaxes[$amountFieldName] ?? null;
        if (null !== $amount) {
            $result = $amount - $discountAmount;
            if ($result < 0) {
                $result = 0.0;
            }
        }

        return $result;
    }

    private function getLineItemDiscount(array $appliedDiscounts, ?int $lineItemId): string|float
    {
        if (!\array_key_exists($lineItemId, $appliedDiscounts)) {
            return 0.0;
        }

        return $appliedDiscounts[$lineItemId];
    }

    /**
     * @param CustomizeLoadedDataContext $context
     * @param int[]                      $lineItemIds
     * @param int                        $lineItemId
     *
     * @return array
     */
    private function getLineItemTaxes(
        CustomizeLoadedDataContext $context,
        array $lineItemIds,
        int $lineItemId
    ): array {
        $allTaxes = $this->lineItemTaxesProvider->getTaxes($context, $lineItemIds);
        if (!\array_key_exists($lineItemId, $allTaxes)) {
            return [];
        }

        return $allTaxes[$lineItemId];
    }
}

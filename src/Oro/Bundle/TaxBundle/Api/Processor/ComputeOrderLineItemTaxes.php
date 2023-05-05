<?php

namespace Oro\Bundle\TaxBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\TaxBundle\Api\OrderLineItemTaxesProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for the following fields for OrderLineItem entity:
 * * unitPriceIncludingTax
 * * unitPriceExcludingTax
 * * unitPriceTaxAmount
 * * rowTotalIncludingTax
 * * rowTotalExcludingTax
 * * rowTotalTaxAmount
 * * taxes
 */
class ComputeOrderLineItemTaxes implements ProcessorInterface
{
    private const FIELD_NAMES = [
        OrderLineItemTaxesProvider::UNIT_PRICE_INCLUDING_TAX,
        OrderLineItemTaxesProvider::UNIT_PRICE_EXCLUDING_TAX,
        OrderLineItemTaxesProvider::UNIT_PRICE_TAX_AMOUNT,
        OrderLineItemTaxesProvider::ROW_TOTAL_INCLUDING_TAX,
        OrderLineItemTaxesProvider::ROW_TOTAL_EXCLUDING_TAX,
        OrderLineItemTaxesProvider::ROW_TOTAL_TAX_AMOUNT,
        OrderLineItemTaxesProvider::TAXES
    ];

    private OrderLineItemTaxesProvider $lineItemTaxesProvider;
    private ValueTransformer $valueTransformer;

    public function __construct(
        OrderLineItemTaxesProvider $lineItemTaxesProvider,
        ValueTransformer $valueTransformer
    ) {
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
            $context->setData($this->applyTaxes($context, $data, $lineItemIdFieldName));
        }
    }

    private function applyTaxes(
        CustomizeLoadedDataContext $context,
        array $data,
        string $lineItemIdFieldName
    ): array {
        $lineItemIds = $context->getIdentifierValues($data, $lineItemIdFieldName);
        $allTaxes = $this->lineItemTaxesProvider->getTaxes($context, $lineItemIds);
        $normalizationContext = $context->getNormalizationContext();
        foreach ($data as $key => $item) {
            $lineItemId = $item[$lineItemIdFieldName];
            $lineItemTaxes = [];
            if (\array_key_exists($lineItemId, $allTaxes)) {
                $lineItemTaxes = $allTaxes[$lineItemId];
            }
            foreach (self::FIELD_NAMES as $fieldName) {
                if ($context->isFieldRequested($fieldName, $item)) {
                    $data[$key][$fieldName] = $this->getFieldValue($lineItemTaxes, $fieldName, $normalizationContext);
                }
            }
        }

        return $data;
    }

    private function getFieldValue(array $lineItemTaxes, string $fieldName, array $normalizationContext): mixed
    {
        $result = $lineItemTaxes[$fieldName] ?? null;
        if (OrderLineItemTaxesProvider::TAXES !== $fieldName) {
            $result = $this->valueTransformer->transformValue(
                $result,
                DataType::MONEY,
                $normalizationContext
            );
        }

        return $result;
    }
}

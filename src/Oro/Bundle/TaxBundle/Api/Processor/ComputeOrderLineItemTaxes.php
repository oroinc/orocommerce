<?php

namespace Oro\Bundle\TaxBundle\Api\Processor;

use Brick\Math\BigDecimal;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\TaxBundle\Api\OrderLineItemTaxesProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\DBAL\Types\MoneyType;

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

    /** @var OrderLineItemTaxesProvider */
    private $lineItemTaxesProvider;

    /**
     * @param OrderLineItemTaxesProvider $lineItemTaxesProvider
     */
    public function __construct(OrderLineItemTaxesProvider $lineItemTaxesProvider)
    {
        $this->lineItemTaxesProvider = $lineItemTaxesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getResult();
        if (!is_array($data) || empty($data)) {
            return;
        }

        if (!$context->isAtLeastOneFieldRequestedForCollection(self::FIELD_NAMES, $data)) {
            return;
        }

        $lineItemIdFieldName = $context->getResultFieldName('id');
        if ($lineItemIdFieldName) {
            $context->setResult($this->applyTaxes($context, $data, $lineItemIdFieldName));
        }
    }

    /**
     * @param CustomizeLoadedDataContext $context
     * @param array                      $data
     * @param string                     $lineItemIdFieldName
     *
     * @return array
     */
    private function applyTaxes(
        CustomizeLoadedDataContext $context,
        array $data,
        string $lineItemIdFieldName
    ): array {
        $lineItemIds = $context->getIdentifierValues($data, $lineItemIdFieldName);
        $allTaxes = $this->lineItemTaxesProvider->getTaxes($context, $lineItemIds);
        foreach ($data as $key => $item) {
            $lineItemId = $item[$lineItemIdFieldName];
            $lineItemTaxes = [];
            if (array_key_exists($lineItemId, $allTaxes)) {
                $lineItemTaxes = $allTaxes[$lineItemId];
            }
            foreach (self::FIELD_NAMES as $fieldName) {
                if ($context->isFieldRequested($fieldName, $item)) {
                    $data[$key][$fieldName] = $this->getFieldValue($lineItemTaxes, $fieldName);
                }
            }
        }

        return $data;
    }

    /**
     * @param array  $lineItemTaxes
     * @param string $fieldName
     *
     * @return mixed
     */
    private function getFieldValue(array $lineItemTaxes, string $fieldName)
    {
        $result = $lineItemTaxes[$fieldName] ?? null;
        if (OrderLineItemTaxesProvider::TAXES !== $fieldName) {
            $result = $this->getMoneyValue($result);
        }

        return $result;
    }

    /**
     * @param mixed $value
     *
     * @return string|null
     */
    private function getMoneyValue($value)
    {
        if (null !== $value) {
            $value = (string)BigDecimal::of($value)->toScale(MoneyType::TYPE_SCALE);
        }

        return $value;
    }
}

<?php

namespace Oro\Bundle\TaxBundle\Api\Processor;

use Brick\Math\BigDecimal;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\TaxBundle\Api\OrderTaxesProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\DBAL\Types\MoneyType;

/**
 * Computes values for the following fields for Order entity:
 * * totalIncludingTax
 * * totalExcludingTax
 * * totalTaxAmount
 */
class ComputeOrderTaxes implements ProcessorInterface
{
    private const FIELD_NAMES = [
        OrderTaxesProvider::TOTAL_INCLUDING_TAX,
        OrderTaxesProvider::TOTAL_EXCLUDING_TAX,
        OrderTaxesProvider::TOTAL_TAX_AMOUNT
    ];

    /** @var OrderTaxesProvider */
    private $orderTaxesProvider;

    /**
     * @param OrderTaxesProvider $orderTaxesProvider
     */
    public function __construct(OrderTaxesProvider $orderTaxesProvider)
    {
        $this->orderTaxesProvider = $orderTaxesProvider;
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

        $orderIdFieldName = $context->getResultFieldName('id');
        if ($orderIdFieldName) {
            $context->setResult($this->applyTaxes($context, $data, $orderIdFieldName));
        }
    }

    /**
     * @param CustomizeLoadedDataContext $context
     * @param array                      $data
     * @param string                     $orderIdFieldName
     *
     * @return array
     */
    private function applyTaxes(
        CustomizeLoadedDataContext $context,
        array $data,
        string $orderIdFieldName
    ): array {
        $orderIds = $context->getIdentifierValues($data, $orderIdFieldName);
        $allTaxes = $this->orderTaxesProvider->getTaxes($context, $orderIds);
        foreach ($data as $key => $item) {
            $orderId = $item[$orderIdFieldName];
            $orderTaxes = [];
            if (array_key_exists($orderId, $allTaxes)) {
                $orderTaxes = $allTaxes[$orderId];
            }
            foreach (self::FIELD_NAMES as $fieldName) {
                if ($context->isFieldRequested($fieldName, $item)) {
                    $data[$key][$fieldName] = $this->getFieldValue($orderTaxes, $fieldName);
                }
            }
        }

        return $data;
    }

    /**
     * @param array  $orderTaxes
     * @param string $fieldName
     *
     * @return mixed
     */
    private function getFieldValue(array $orderTaxes, string $fieldName)
    {
        return $this->getMoneyValue($orderTaxes[$fieldName] ?? null);
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

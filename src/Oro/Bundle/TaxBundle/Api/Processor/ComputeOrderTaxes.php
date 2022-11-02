<?php

namespace Oro\Bundle\TaxBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\TaxBundle\Api\OrderTaxesProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

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

    /** @var ValueTransformer */
    private $valueTransformer;

    public function __construct(
        OrderTaxesProvider $orderTaxesProvider,
        ValueTransformer $valueTransformer
    ) {
        $this->orderTaxesProvider = $orderTaxesProvider;
        $this->valueTransformer = $valueTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isAtLeastOneFieldRequestedForCollection(self::FIELD_NAMES, $data)) {
            return;
        }

        $orderIdFieldName = $context->getResultFieldName('id');
        if ($orderIdFieldName) {
            $context->setData($this->applyTaxes($context, $data, $orderIdFieldName));
        }
    }

    private function applyTaxes(
        CustomizeLoadedDataContext $context,
        array $data,
        string $orderIdFieldName
    ): array {
        $orderIds = $context->getIdentifierValues($data, $orderIdFieldName);
        $allTaxes = $this->orderTaxesProvider->getTaxes($context, $orderIds);
        $normalizationContext = $context->getNormalizationContext();
        foreach ($data as $key => $item) {
            $orderId = $item[$orderIdFieldName];
            $orderTaxes = [];
            if (array_key_exists($orderId, $allTaxes)) {
                $orderTaxes = $allTaxes[$orderId];
            }
            foreach (self::FIELD_NAMES as $fieldName) {
                if ($context->isFieldRequested($fieldName, $item)) {
                    $data[$key][$fieldName] = $this->getFieldValue($orderTaxes, $fieldName, $normalizationContext);
                }
            }
        }

        return $data;
    }

    /**
     * @param array  $orderTaxes
     * @param string $fieldName
     * @param array  $normalizationContext
     *
     * @return mixed
     */
    private function getFieldValue(array $orderTaxes, string $fieldName, array $normalizationContext)
    {
        return $this->valueTransformer->transformValue(
            $orderTaxes[$fieldName] ?? null,
            DataType::MONEY,
            $normalizationContext
        );
    }
}

<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "currency", "total" and "subTotal" fields for a shopping list.
 */
class ComputeShoppingListTotal implements ProcessorInterface
{
    public const SHOPPING_LIST_SUB_TOTALS = 'shopping_list_sub_totals';

    private DoctrineHelper $doctrineHelper;
    private TotalProcessorProvider $totalProvider;
    private ValueTransformer $valueTransformer;

    public function __construct(DoctrineHelper $doctrineHelper, TotalProcessorProvider $totalProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->totalProvider = $totalProvider;
    }

    public function setValueTransformer(ValueTransformer $valueTransformer): void
    {
        $this->valueTransformer = $valueTransformer;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $totalFieldName = 'total';
        $subTotalFieldName = 'subTotal';
        $currencyFieldName = 'currency';
        if (\array_key_exists($totalFieldName, $data) || \array_key_exists($subTotalFieldName, $data)) {
            // the computing values are already set
            return;
        }

        $config = $context->getConfig();
        $totalField = $config->getField($totalFieldName);
        $subTotalField = $config->getField($subTotalFieldName);
        $currencyField = $config->getField($currencyFieldName);
        if (null === $totalField && null === $subTotalField && null === $currencyField) {
            // only identifier field was requested
            return;
        }
        if ($totalField->isExcluded() && $subTotalField->isExcluded() && $currencyField->isExcluded()) {
            // the computing fields were not requested
            return;
        }

        $data = $this->computeFields(
            $data,
            $config,
            $currencyFieldName,
            $currencyField,
            $totalFieldName,
            $totalField,
            $subTotalFieldName,
            $subTotalField,
            $context
        );
        $context->setData($data);
    }

    private function computeFields(
        array $data,
        EntityDefinitionConfig $config,
        string $currencyFieldName,
        EntityDefinitionFieldConfig $currencyField,
        string $totalFieldName,
        EntityDefinitionFieldConfig $totalField,
        string $subTotalFieldName,
        EntityDefinitionFieldConfig $subTotalField,
        CustomizeLoadedDataContext $context
    ): array {
        $em = $this->doctrineHelper->getEntityManagerForClass(ShoppingList::class);
        $shoppingList = $em->getReference(ShoppingList::class, $data[$config->findFieldNameByPropertyPath('id')]);
        $em->refresh($shoppingList);
        $computedTotal = $this->totalProvider->getTotal($shoppingList);

        if (!$totalField->isExcluded()) {
            $data[$totalFieldName] = $this->valueTransformer->transformValue(
                $computedTotal->getAmount(),
                DataType::MONEY,
                $context->getNormalizationContext()
            );
        }
        if (!$currencyField->isExcluded()) {
            $data[$currencyFieldName] = $computedTotal->getCurrency();
        }
        if (!$subTotalField->isExcluded()) {
            $computedSubtotals = $this->totalProvider->getSubtotals($shoppingList);
            $context->set(self::SHOPPING_LIST_SUB_TOTALS, $computedSubtotals);
            $subTotal = null;
            foreach ($computedSubtotals as $computedValue) {
                if ('subtotal' === $computedValue->getType()) {
                    $subTotal = $computedValue->getAmount();
                    break;
                }
            }
            if (null !== $subTotal) {
                $subTotal = $this->valueTransformer->transformValue(
                    $subTotal,
                    DataType::MONEY,
                    $context->getNormalizationContext()
                );
            }
            $data[$subTotalFieldName] = $subTotal;
        }

        return $data;
    }
}

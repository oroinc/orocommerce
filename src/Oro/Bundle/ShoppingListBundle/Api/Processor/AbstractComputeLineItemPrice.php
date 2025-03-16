<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "currency" and "value" fields for a shopping list item.
 * THIS CLASS IS DEPRECATED AND WILL BE REMOVED IN THE NEXT MAJOR RELEASE.
 */
abstract class AbstractComputeLineItemPrice implements ProcessorInterface
{
    protected ManagerRegistry $managerRegistry;

    protected ProductLineItemPriceProviderInterface $productLineItemPriceProvider;

    protected ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
        $this->priceScopeCriteriaFactory = $productPriceScopeCriteriaFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $valueFieldName = 'value';
        $currencyFieldName = 'currency';
        if (\array_key_exists($valueFieldName, $data) || \array_key_exists($currencyFieldName, $data)) {
            // the computing values are already set
            return;
        }

        $config = $context->getConfig();
        $valueField = $config->getField($valueFieldName);
        $currencyField = $config->getField($currencyFieldName);
        if (null === $valueField && null === $currencyField) {
            // only identifier field was requested
            return;
        }

        if ($valueField->isExcluded() && $currencyField->isExcluded()) {
            // none of computing fields was requested
            return;
        }

        $productLineItemPrice = $this->getProductLineItemPrice($context);
        $data = $this->setComputedFields(
            $data,
            $currencyFieldName,
            $currencyField,
            $valueFieldName,
            $valueField,
            $productLineItemPrice
        );
        $context->setData($data);
    }

    abstract protected function getShoppingListLineItem(CustomizeLoadedDataContext $context): ?LineItem;

    protected function setComputedFields(
        array $data,
        string $currencyFieldName,
        EntityDefinitionFieldConfig $currencyField,
        string $valueFieldName,
        EntityDefinitionFieldConfig $valueField,
        ?ProductLineItemPrice $productLineItemPrice
    ): array {
        $price = $productLineItemPrice?->getPrice();

        if (!$valueField->isExcluded()) {
            $priceValue = $price?->getValue();
            if (null !== $priceValue) {
                $priceValue = (string)$priceValue;
            }
            $data[$valueFieldName] = $priceValue;
        }
        if (!$currencyField->isExcluded()) {
            $data[$currencyFieldName] = $price?->getCurrency();
        }

        return $data;
    }

    protected function getProductLineItemPrice(CustomizeLoadedDataContext $context): ?ProductLineItemPrice
    {
        $lineItem = $this->getShoppingListLineItem($context);
        if ($lineItem === null) {
            return null;
        }

        $sharedProductLineItemPrices = $context->getSharedData()->get('product_line_item_prices') ?? [];
        $lineItemHash = spl_object_hash($lineItem);
        if (!isset($sharedProductLineItemPrices[$lineItemHash])) {
            $shoppingList = $lineItem->getShoppingList();
            $sharedProductLineItemPrices = $this->productLineItemPriceProvider
                ->getProductLineItemsPrices(
                    [$lineItemHash => $lineItem],
                    $this->priceScopeCriteriaFactory->createByContext($shoppingList),
                    $shoppingList->getCurrency()
                );
        }

        return $sharedProductLineItemPrices[$lineItemHash] ?? null;
    }
}

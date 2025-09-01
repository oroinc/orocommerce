<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values of "currency", "price" and "subTotal" fields for CheckoutKitItem entity.
 */
class ComputeCheckoutProductKitItemLineItemPrice implements ProcessorInterface
{
    public function __construct(
        private readonly ProductLineItemPriceProviderInterface $productLineItemPriceProvider,
        private readonly ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory,
        private readonly ValueTransformer $valueTransformer,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $config = $context->getConfig();
        $priceFieldName = $context->getResultFieldName('price', $config);
        $currencyFieldName = $context->getResultFieldName('currency', $config);
        $subtotalFieldName = $context->getResultFieldName('subTotal', $config);
        $isPriceFieldRequested = $context->isFieldRequested($priceFieldName);
        $isCurrencyFieldRequested = $context->isFieldRequested($currencyFieldName);
        $isSubtotalFieldRequested = $context->isFieldRequested($subtotalFieldName);
        if (!$isPriceFieldRequested && !$isCurrencyFieldRequested && !$isSubtotalFieldRequested) {
            return;
        }

        $data = $context->getData();
        $dataMap = $this->getDataMap($data, $context->getResultFieldName('id', $config));
        $productPrices = $this->getProductPrices(array_keys($dataMap));
        $normalizationContext = $context->getNormalizationContext();
        foreach ($dataMap as $id => $key) {
            $productPrice = $productPrices[$id] ?? null;
            if ($isPriceFieldRequested) {
                $data[$key][$priceFieldName] = $this->normalizeValue(
                    $productPrice?->getPrice()?->getValue(),
                    $config->getField($priceFieldName),
                    $normalizationContext
                );
            }
            if ($isCurrencyFieldRequested) {
                $data[$key][$currencyFieldName] = $productPrice?->getPrice()?->getCurrency();
            }
            if ($isSubtotalFieldRequested) {
                $data[$key][$subtotalFieldName] = $this->normalizeValue(
                    $productPrice?->getSubtotal(),
                    $config->getField($subtotalFieldName),
                    $normalizationContext
                );
            }
        }
        $context->setData($data);
    }

    private function getDataMap(array $data, string $idFieldName): array
    {
        $dataMap = [];
        foreach ($data as $key => $item) {
            $dataMap[$item[$idFieldName]] = $key;
        }

        return $dataMap;
    }

    /**
     * @return array<int, ?ProductLineItemPrice> [line item id => price, ...]
     */
    private function getProductPrices(array $kitItemLineItemIds): array
    {
        $result = [];
        $mapOfLineItems = $this->getMapOfLineItems($kitItemLineItemIds);
        foreach ($mapOfLineItems as [$lineItems, $checkout]) {
            $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
                $lineItems,
                $this->productPriceScopeCriteriaFactory->createByContext($checkout),
                $checkout->getCurrency()
            );
            foreach ($productLineItemPrices as $productLineItemPrice) {
                if (!$productLineItemPrice instanceof ProductKitLineItemPrice) {
                    continue;
                }
                $kitItemPrices = $productLineItemPrice->getKitItemLineItemPrices();
                foreach ($kitItemPrices as $kitItemPrice) {
                    $result[$kitItemPrice->getKitItemLineItem()->getEntityIdentifier()] = $kitItemPrice;
                }
            }
        }

        return $result;
    }

    private function getMapOfLineItems(array $kitItemLineItemIds): array
    {
        $mapOfLineItems = [];
        $lineItems = $this->getLineItems($kitItemLineItemIds);
        foreach ($lineItems as $lineItem) {
            /** @var Checkout $checkout */
            $checkout = $lineItem->getCheckout();
            if (!isset($mapOfLineItems[$checkout->getId()])) {
                $mapOfLineItems[$checkout->getId()] = [[], $checkout];
            }
            $mapOfLineItems[$checkout->getId()][0][] = $lineItem;
        }

        return $mapOfLineItems;
    }

    /**
     * @return CheckoutLineItem[]
     */
    private function getLineItems(array $kitItemLineItemIds): array
    {
        /** @var CheckoutProductKitItemLineItem[] $kitItemLineItems */
        $kitItemLineItems = $this->doctrineHelper->createQueryBuilder(CheckoutProductKitItemLineItem::class, 'kli')
            ->select('kli, li, c, p, pu, ki, kii')
            ->leftJoin('kli.lineItem', 'li')
            ->leftJoin('li.checkout', 'c')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.productUnit', 'pu')
            ->leftJoin('li.kitItemLineItems', 'ki')
            ->leftJoin('ki.kitItem', 'kii')
            ->where('kli.id IN (:ids)')
            ->setParameter('ids', $kitItemLineItemIds)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();

        $lineItems = [];
        foreach ($kitItemLineItems as $kitItemLineItem) {
            /** @var CheckoutLineItem $lineItem */
            $lineItem = $kitItemLineItem->getLineItem();
            if (!isset($lineItems[$lineItem->getId()])) {
                $lineItems[$lineItem->getId()] = $lineItem;
            }
        }

        return array_values($lineItems);
    }

    private function normalizeValue(mixed $value, EntityDefinitionFieldConfig $config, array $context): mixed
    {
        return $this->valueTransformer->transformFieldValue($value, $config->toArray(true), $context);
    }
}

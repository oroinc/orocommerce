<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "currency", "price" and "subTotal" fields for CheckoutLineItem entity.
 */
class ComputeCheckoutLineItemPrice implements ProcessorInterface
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
    private function getProductPrices(array $lineItemIds): array
    {
        $result = [];
        $mapOfLineItems = $this->getMapOfLineItems($lineItemIds);
        foreach ($mapOfLineItems as [$lineItems, $checkout]) {
            $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
                $lineItems,
                $this->productPriceScopeCriteriaFactory->createByContext($checkout),
                $checkout->getCurrency()
            );
            foreach ($productLineItemPrices as $productLineItemPrice) {
                $result[$productLineItemPrice->getLineItem()->getEntityIdentifier()] = $productLineItemPrice;
            }
        }

        return $result;
    }

    /**
     * @return array [checkout id => [[line item, ...], checkout], ...]
     */
    private function getMapOfLineItems(array $lineItemIds): array
    {
        $mapOfLineItems = [];
        $lineItems = $this->getLineItems($lineItemIds);
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
    private function getLineItems(array $lineItemIds): array
    {
        return $this->doctrineHelper->createQueryBuilder(CheckoutLineItem::class, 'li')
            ->select('li, c, p, pu, ki, kii')
            ->leftJoin('li.checkout', 'c')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.productUnit', 'pu')
            ->leftJoin('li.kitItemLineItems', 'ki')
            ->leftJoin('ki.kitItem', 'kii')
            ->where('li.id IN (:ids)')
            ->setParameter('ids', $lineItemIds)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getResult();
    }

    private function normalizeValue(mixed $value, EntityDefinitionFieldConfig $config, array $context): mixed
    {
        return $this->valueTransformer->transformFieldValue($value, $config->toArray(true), $context);
    }
}

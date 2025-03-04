<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "currency" and "price" fields for a checkout kit item.
 */
class ComputeCheckoutProductKitItemLineItemPrice implements ProcessorInterface
{
    private const string PRICE_FIELD_NAME = 'price';
    private const string CURRENCY_FIELD_NAME = 'currency';

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

        $data = $context->getData();
        $isPriceFieldRequested = $context->isFieldRequested(self::PRICE_FIELD_NAME, $data);
        $isCurrencyFieldRequested = $context->isFieldRequested(self::CURRENCY_FIELD_NAME, $data);
        if (!$isPriceFieldRequested && !$isCurrencyFieldRequested) {
            return;
        }

        $productPrice = $this->getProductPrice(
            $data[$context->getResultFieldName('id')],
            $this->getKitItemId($data, $context)
        );
        if ($isPriceFieldRequested) {
            $data[self::PRICE_FIELD_NAME] = $this->valueTransformer->transformFieldValue(
                $productPrice?->getValue(),
                $context->getConfig()->getField(self::PRICE_FIELD_NAME)->toArray(true),
                $context->getNormalizationContext()
            );
        }
        if ($isCurrencyFieldRequested) {
            $data[self::CURRENCY_FIELD_NAME] = $productPrice?->getCurrency();
        }
        $context->setData($data);
    }

    private function getProductPrice(int $kitItemLineItemId, int $kitItemId): ?Price
    {
        $productLineItemPrice = $this->getProductLineItemPrice($kitItemLineItemId);
        if (!$productLineItemPrice instanceof ProductKitLineItemPrice) {
            return null;
        }

        $kitItemLineItemPrices = $productLineItemPrice->getKitItemLineItemPrices();
        if (!isset($kitItemLineItemPrices[$kitItemId])) {
            return null;
        }

        return $kitItemLineItemPrices[$kitItemId]->getPrice();
    }

    private function getProductLineItemPrice(int $kitItemLineItemId): ?ProductLineItemPrice
    {
        $lineItem = $this->getLineItem($kitItemLineItemId);
        if (null === $lineItem) {
            return null;
        }

        $checkout = $lineItem->getCheckout();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->productPriceScopeCriteriaFactory->createByContext($checkout),
            $checkout->getCurrency()
        );

        return $productLineItemPrices[0] ?? null;
    }

    private function getLineItem(int $kitItemLineItemId): ?CheckoutLineItem
    {
        /** @var CheckoutProductKitItemLineItem|null $kitItemLineItem */
        $kitItemLineItem = $this->doctrineHelper->createQueryBuilder(CheckoutProductKitItemLineItem::class, 'kli')
            ->select('kli, li, c, p, pu, ki, kii')
            ->leftJoin('kli.lineItem', 'li')
            ->leftJoin('li.checkout', 'c')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.productUnit', 'pu')
            ->leftJoin('li.kitItemLineItems', 'ki')
            ->leftJoin('ki.kitItem', 'kii')
            ->where('kli.id = :id')
            ->setParameter('id', $kitItemLineItemId)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getOneOrNullResult();

        return $kitItemLineItem?->getLineItem();
    }

    private function getKitItemId(array $data, CustomizeLoadedDataContext $context): int
    {
        $kitItemFieldName = $context->getResultFieldName('kitItem');
        $kitItemIdFieldName = $context->getResultFieldName(
            'id',
            $context->getConfig()->getField($kitItemFieldName)->getTargetEntity()
        );

        return $data[$kitItemFieldName][$kitItemIdFieldName];
    }
}

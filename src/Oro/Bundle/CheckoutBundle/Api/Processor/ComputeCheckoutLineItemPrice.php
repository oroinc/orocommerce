<?php

namespace Oro\Bundle\CheckoutBundle\Api\Processor;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "currency" and "price" fields for a checkout line item.
 */
class ComputeCheckoutLineItemPrice implements ProcessorInterface
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

        $productPrice = $this->getProductPrice($data[$context->getResultFieldName('id')]);
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

    private function getProductPrice(int $lineItemId): ?Price
    {
        $lineItem = $this->getLineItem($lineItemId);
        if (null === $lineItem) {
            return null;
        }

        $checkout = $lineItem->getCheckout();
        $productLineItemPrices = $this->productLineItemPriceProvider->getProductLineItemsPrices(
            [$lineItem],
            $this->productPriceScopeCriteriaFactory->createByContext($checkout),
            $checkout->getCurrency()
        );
        if (!isset($productLineItemPrices[0])) {
            return null;
        }

        return $productLineItemPrices[0]->getPrice();
    }

    private function getLineItem(int $lineItemId): ?CheckoutLineItem
    {
        return $this->doctrineHelper->createQueryBuilder(CheckoutLineItem::class, 'li')
            ->select('li, c, p, pu, ki, kii')
            ->leftJoin('li.checkout', 'c')
            ->leftJoin('li.product', 'p')
            ->leftJoin('li.productUnit', 'pu')
            ->leftJoin('li.kitItemLineItems', 'ki')
            ->leftJoin('ki.kitItem', 'kii')
            ->where('li.id = :id')
            ->setParameter('id', $lineItemId)
            ->getQuery()
            ->setHint(Query::HINT_REFRESH, true)
            ->getOneOrNullResult();
    }
}

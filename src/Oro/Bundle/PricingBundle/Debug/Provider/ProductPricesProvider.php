<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Provide information about current product prices in CPL actual for customer/website.
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class ProductPricesProvider
{
    public function __construct(
        private DebugProductPricesPriceListRequestHandler $requestHandler,
        private ManagerRegistry $registry,
        private ShardManager $shardManager
    ) {
    }

    public function getCurrentPrices(Product $product): array
    {
        $priceList = $this->requestHandler->getPriceList();
        if (!$priceList) {
            return [];
        }

        /** @var ProductPriceDTO[] $priceDtos */
        $priceDtos = $this->registry->getRepository(CombinedProductPrice::class)->getPricesBatch(
            $this->shardManager,
            $priceList->getId(),
            [$product->getId()]
        );

        $prices = [];
        foreach ($priceDtos as $dto) {
            $prices[$dto->getPrice()->getCurrency()][] = [
                'price' => $dto->getPrice(),
                'unitCode' => $dto->getUnit()?->getCode(),
                'quantity' => $dto->getQuantity(),
            ];
        }

        foreach ($prices as &$pricesForCurrency) {
            ArrayUtil::sortBy($pricesForCurrency, false, 'quantity');
        }

        return $prices;
    }
}

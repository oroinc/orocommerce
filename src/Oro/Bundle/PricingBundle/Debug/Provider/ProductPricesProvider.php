<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Provide information about current product prices in CPL actual for customer/website.
 */
class ProductPricesProvider
{
    public function __construct(
        private DebugProductPricesPriceListRequestHandler $requestHandler,
        private ProductPriceStorageInterface $priceStorage
    ) {
    }

    public function getCurrentPrices(Product $product): array
    {
        $criteria = new ProductPriceScopeCriteria();
        $criteria->setWebsite($this->requestHandler->getWebsite());
        $criteria->setCustomer($this->requestHandler->getCustomer());

        /** @var ProductPriceDTO[] $priceDtos */
        $priceDtos = $this->priceStorage->getPrices(
            $criteria,
            [$product]
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

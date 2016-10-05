<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\Repository\Stub;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository as BaseRepository;

class CombinedProductPriceRepository extends BaseRepository
{
    protected $pricesForProductsByPriceListResult = [];

    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getPricesForProductsByPriceList(CombinedPriceList $priceList, array $productIds, $currency = null)
    {
        return $this->pricesForProductsByPriceListResult;
    }

    /**
     * @return CombinedProductPriceRepository
     */
    public static function withoutPricesForProductsByPriceList()
    {
        return new static();
    }

    /**
     * @param CombinedProductPrice[] $combinedProductPrices
     *
     * @return CombinedProductPriceRepository
     */
    public static function withPricesForProductsByPriceList(array $combinedProductPrices)
    {
        $repository = new static();

        $repository->pricesForProductsByPriceListResult = $combinedProductPrices;

        return $repository;
    }
}

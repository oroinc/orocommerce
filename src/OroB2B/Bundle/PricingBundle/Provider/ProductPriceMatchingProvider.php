<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductPriceMatchingProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $className;

    /**
     * @param ManagerRegistry $registry
     * @param string $className
     */
    public function __construct(ManagerRegistry $registry, $className)
    {
        $this->registry = $registry;
        $this->className = $className;
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @param int $quantity
     * @param string $currency
     * @param null|int $priceListId
     * @return Price
     */
    public function matchPrice(Product $product, ProductUnit $productUnit, $quantity, $currency, $priceListId = null)
    {
        return Price::create(mt_rand(1, 1000000)/100, $currency);
    }

    /**
     * @return ProductPriceRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass($this->className)->getRepository($this->className);
    }
}

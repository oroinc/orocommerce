<?php

namespace Oro\Bundle\PricingBundle\Event;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * It published immediately after the removal of prices.
 */
class ProductPriceRemove extends Event
{
    const NAME = 'oro_pricing.product_price.remove';

    /**
     * @var BaseProductPrice
     */
    protected $price;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * ProductPriceRemove constructor.
     */
    public function __construct(ProductPrice $price)
    {
        $this->price = $price;
    }

    /**
     * @return ProductPrice
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): ?EntityManager
    {
        return $this->entityManager;
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }
}

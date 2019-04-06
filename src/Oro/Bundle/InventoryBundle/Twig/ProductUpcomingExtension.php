<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides twig functions to determine upcoming product status and availability date.
 */
class ProductUpcomingExtension extends \Twig_Extension
{
    /**
     * @var UpcomingProductProvider
     */
    protected $provider;

    public function __construct(UpcomingProductProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_inventory_is_product_upcoming', [$this, 'isUpcomingProduct']),
            new \Twig_SimpleFunction(
                'oro_inventory_upcoming_product_availability_date',
                [$this, 'getUpcomingAvailabilityDate']
            )
        ];
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isUpcomingProduct(Product $product): bool
    {
        return $this->provider->isUpcoming($product);
    }

    /**
     * @param Product $product
     * @return \DateTime|null
     * @throws \LogicException
     */
    public function getUpcomingAvailabilityDate(Product $product): ?\DateTime
    {
        return $this->provider->getAvailabilityDate($product);
    }
}

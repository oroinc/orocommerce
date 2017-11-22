<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductUpcomingExtension extends \Twig_Extension
{
    /**
     * @var ProductUpcomingProvider
     */
    protected $provider;

    public function __construct(ProductUpcomingProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_inventory_product_is_upcoming', [$this, 'isUpcoming']),
            new \Twig_SimpleFunction('oro_inventory_product_availability_date', [$this, 'getAvailabilityDate']),
        ];
    }

    /**
     * @param Product $product
     * @return bool|null
     */
    public function isUpcoming(Product $product)
    {
        return $this->provider->isUpcoming($product);
    }

    /**
     * @param Product $product
     * @return \DateTime|null
     */
    public function getAvailabilityDate(Product $product)
    {
        return $this->provider->getAvailabilityDate($product);
    }
}

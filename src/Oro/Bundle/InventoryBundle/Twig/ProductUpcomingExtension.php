<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to determine upcoming product status and availability date:
 *   - oro_inventory_is_product_upcoming
 *   - oro_inventory_upcoming_product_availability_date
 */
class ProductUpcomingExtension extends AbstractExtension
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
            new TwigFunction('oro_inventory_is_product_upcoming', [$this, 'isUpcomingProduct']),
            new TwigFunction(
                'oro_inventory_upcoming_product_availability_date',
                [$this, 'getUpcomingAvailabilityDate']
            )
        ];
    }

    public function isUpcomingProduct(Product $product): bool
    {
        return $this->provider->isUpcoming($product);
    }

    /**
     * @throws \LogicException
     */
    public function getUpcomingAvailabilityDate(Product $product): ?\DateTime
    {
        return $this->provider->getAvailabilityDate($product);
    }
}

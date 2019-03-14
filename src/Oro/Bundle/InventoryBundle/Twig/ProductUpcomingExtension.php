<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides twig functions to determine upcoming product status and availability date.
 */
class ProductUpcomingExtension extends \Twig_Extension
{
    /**
     * @var ProductUpcomingProvider
     */
    protected $provider;

    /**
     * @var UpcomingProductProvider
     */
    protected $upcomingProductProvider;

    public function __construct(ProductUpcomingProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param UpcomingProductProvider $upcomingProductProvider
     */
    public function setUpcomingProductProvider(UpcomingProductProvider $upcomingProductProvider): void
    {
        $this->upcomingProductProvider = $upcomingProductProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_inventory_product_is_upcoming', [$this, 'isUpcoming']),
            new \Twig_SimpleFunction('oro_inventory_product_availability_date', [$this, 'getAvailabilityDate']),
            new \Twig_SimpleFunction('oro_inventory_is_product_upcoming', [$this, 'isUpcomingProduct']),
            new \Twig_SimpleFunction(
                'oro_inventory_upcoming_product_availability_date',
                [$this, 'getUpcomingAvailabilityDate']
            )
        ];
    }

    /**
     * @param Product $product
     * @return bool|null
     * @deprecated use isUpcomingProduct instead
     */
    public function isUpcoming(Product $product)
    {
        return $this->provider->isUpcoming($product);
    }

    /**
     * @param Product $product
     * @return \DateTime|null
     * @deprecated use getUpcomingAvailabilityDate instead
     */
    public function getAvailabilityDate(Product $product)
    {
        return $this->provider->getAvailabilityDate($product);
    }

    /**
     * @param Product $product
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isUpcomingProduct(Product $product): bool
    {
        return $this->getUpcomingProductProvider()->isUpcoming($product);
    }

    /**
     * @param Product $product
     * @return \DateTime|null
     * @throws \LogicException
     * @throws \InvalidArgumentException
     */
    public function getUpcomingAvailabilityDate(Product $product): ?\DateTime
    {
        return $this->getUpcomingProductProvider()->getAvailabilityDate($product);
    }

    /**
     * @return UpcomingProductProvider
     * @throws \InvalidArgumentException
     */
    private function getUpcomingProductProvider(): UpcomingProductProvider
    {
        if (!$this->upcomingProductProvider) {
            throw new \InvalidArgumentException(sprintf('Upcoming product provider is not set for %s', __CLASS__));
        }

        return $this->upcomingProductProvider;
    }
}

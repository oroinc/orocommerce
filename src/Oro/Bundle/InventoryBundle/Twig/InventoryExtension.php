<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to determine upcoming product status and availability date:
 *   - oro_inventory_is_product_upcoming
 *   - oro_inventory_upcoming_product_availability_date
 *
 * Provides a Twig function to check if the product is a "low inventory" item:
 *   - oro_is_low_inventory_product
 */
class InventoryExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?UpcomingProductProvider $upcomingProductProvider = null;
    private ?LowInventoryProvider $lowInventoryProvider = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_inventory_is_product_upcoming',
                [$this, 'isUpcomingProduct']
            ),
            new TwigFunction(
                'oro_inventory_upcoming_product_availability_date',
                [$this, 'getUpcomingAvailabilityDate']
            ),
            new TwigFunction(
                'oro_is_low_inventory_product',
                [$this, 'isLowInventory']
            ),
        ];
    }

    public function isUpcomingProduct(Product $product): bool
    {
        return $this->getUpcomingProductProvider()->isUpcoming($product);
    }

    public function getUpcomingAvailabilityDate(Product $product): ?\DateTime
    {
        return $this->getUpcomingProductProvider()->getAvailabilityDate($product);
    }

    public function isLowInventory(Product $product): bool
    {
        return $this->getLowInventoryProvider()->isLowInventoryProduct($product);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_inventory.provider.upcoming_product_provider' => UpcomingProductProvider::class,
            'oro_inventory.inventory.low_inventory_provider' => LowInventoryProvider::class,
        ];
    }

    private function getUpcomingProductProvider(): UpcomingProductProvider
    {
        if (null === $this->upcomingProductProvider) {
            $this->upcomingProductProvider = $this->container->get('oro_inventory.provider.upcoming_product_provider');
        }

        return $this->upcomingProductProvider;
    }

    private function getLowInventoryProvider(): LowInventoryProvider
    {
        if (null === $this->lowInventoryProvider) {
            $this->lowInventoryProvider = $this->container->get('oro_inventory.inventory.low_inventory_provider');
        }

        return $this->lowInventoryProvider;
    }
}

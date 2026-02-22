<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\InventoryStatusProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
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
 *
 * Provides a Twig function to get code and label for inventory status of given Product or Product View or search item
 *   - oro_inventory_status_code
 *   - oro_inventory_status_label
 */
class InventoryExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
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
            new TwigFunction(
                'oro_inventory_status_code',
                [$this, 'getInventoryStatusCode']
            ),
            new TwigFunction(
                'oro_inventory_status_label',
                [$this, 'getInventoryStatusLabel']
            ),
            new TwigFunction(
                'oro_inventory_availability_code',
                [$this, 'getInventoryAvailabilityCode']
            ),
            new TwigFunction(
                'oro_inventory_availability_label',
                [$this, 'getInventoryAvailabilityLabel']
            ),
        ];
    }

    public function isUpcomingProduct(Product $product): bool
    {
        return $this->getUpcomingProductProvider()->isUpcoming($product);
    }

    public function isUpcomingItem(Product|ProductView|array $product): bool
    {
        if ($product instanceof ProductView && $product->has('is_upcoming')) {
            return \filter_var($product->get('is_upcoming'), FILTER_VALIDATE_BOOLEAN);
        }
        if (is_array($product) && isset($product['is_upcoming'])) {
            return \filter_var($product['is_upcoming'], FILTER_VALIDATE_BOOLEAN);
        }

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

    public function isLowInventoryItem(Product|ProductView|array $product): bool
    {
        if ($product instanceof ProductView && $product->has('low_inventory')) {
            return \filter_var($product->get('low_inventory'), FILTER_VALIDATE_BOOLEAN);
        }
        if (is_array($product) && isset($product['low_inventory'])) {
            return \filter_var($product['low_inventory'], FILTER_VALIDATE_BOOLEAN);
        }

        return $this->getLowInventoryProvider()->isLowInventoryProduct($product);
    }

    public function getInventoryStatusCode(Product|ProductView|array $product): ?string
    {
        return $this->getInventoryStatusProvider()->getCode($product);
    }

    public function getInventoryStatusLabel(Product|ProductView|array $product): ?string
    {
        return $this->getInventoryStatusProvider()->getLabel($product);
    }

    public function getInventoryAvailabilityCode(Product|ProductView|array $product): ?string
    {
        $statusCode = $this->getInventoryStatusProvider()->getCode($product);
        $isInStock = $statusCode === 'prod_inventory_status.in_stock';
        $isLowInventory = $this->isLowInventoryItem($product);
        $isUpcoming = $this->isUpcomingItem($product);

        if ($isLowInventory && $isInStock) {
            $statusCode = 'prod_inventory_status.is_low_inventory';
        }

        if ($isUpcoming) {
            $statusCode = 'prod_inventory_status.is_upcoming';
        }

        return $statusCode;
    }

    public function getInventoryAvailabilityLabel(Product|ProductView|array $product): ?string
    {
        $statusLabel = $this->getInventoryStatusProvider()->getLabel($product);
        $statusCode = $this->getInventoryStatusProvider()->getCode($product);
        $isInStock = $statusCode === 'prod_inventory_status.in_stock';
        $isLowInventory = $this->isLowInventoryItem($product);
        $isUpcoming = $this->isUpcomingItem($product);

        if ($isLowInventory && $isInStock) {
            $statusLabel = 'oro.frontend.shoppinglist.lineitem.status.is_low_inventory';
        }

        if ($isUpcoming) {
            $statusLabel = 'oro.frontend.shoppinglist.lineitem.status.is_upcoming';
        }

        return $statusLabel;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            UpcomingProductProvider::class,
            LowInventoryProvider::class,
            InventoryStatusProvider::class
        ];
    }

    private function getUpcomingProductProvider(): UpcomingProductProvider
    {
        return $this->container->get(UpcomingProductProvider::class);
    }

    private function getLowInventoryProvider(): LowInventoryProvider
    {
        return $this->container->get(LowInventoryProvider::class);
    }

    private function getInventoryStatusProvider(): InventoryStatusProvider
    {
        return $this->container->get(InventoryStatusProvider::class);
    }
}

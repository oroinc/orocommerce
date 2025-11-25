<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\InventoryBundle\Provider\InventoryStatusProvider;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
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
    private ContainerInterface $container;
    private ?UpcomingProductProvider $upcomingProductProvider = null;
    private ?LowInventoryProvider $lowInventoryProvider = null;
    private ?InventoryStatusProvider $inventoryStatusProvider = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        } elseif (is_array($product) && isset($product['is_upcoming'])) {
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
        } elseif (is_array($product) && isset($product['low_inventory'])) {
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
        $isInStock = $statusCode == 'prod_inventory_status.in_stock';
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
        $isInStock = $statusCode == 'prod_inventory_status.in_stock';
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
            'oro_inventory.provider.upcoming_product_provider' => UpcomingProductProvider::class,
            'oro_inventory.inventory.low_inventory_provider' => LowInventoryProvider::class,
            'oro_inventory.provider.inventory_status' => InventoryStatusProvider::class,
            'oro_ui.twig.html_tag' => HtmlTagExtension::class,
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

    private function getInventoryStatusProvider(): InventoryStatusProvider
    {
        if (null === $this->inventoryStatusProvider) {
            $this->inventoryStatusProvider = $this->container->get('oro_inventory.provider.inventory_status');
        }

        return $this->inventoryStatusProvider;
    }
}

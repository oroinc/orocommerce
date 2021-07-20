<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to check if the product is a "low inventory" item:
 *   - oro_is_low_inventory_product
 */
class LowInventoryExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

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
                'oro_is_low_inventory_product',
                [$this, 'isLowInventory']
            )
        ];
    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    public function isLowInventory(Product $product)
    {
        return $this->container->get('oro_inventory.inventory.low_inventory_provider')
            ->isLowInventoryProduct($product);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_inventory.inventory.low_inventory_provider' => LowInventoryProvider::class,
        ];
    }
}

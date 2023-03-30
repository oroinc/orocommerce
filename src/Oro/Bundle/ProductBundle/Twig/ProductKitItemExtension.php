<?php

namespace Oro\Bundle\ProductBundle\Twig;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Provider\ProductKitItemUnitPrecisionProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides TWIG filters and functions for working with product kit items.
 */
class ProductKitItemExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    private ?ProductKitItemUnitPrecisionProvider $kitItemUnitPrecisionProvider = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('oro_product_kit_item_unit_precision', [$this, 'getProductKitItemUnitPrecision']),
        ];
    }

    public function getProductKitItemUnitPrecision(ProductKitItem $productKitItem): int
    {
        return $this->getProductKitItemUnitPrecisionProvider()->getUnitPrecisionByKitItem($productKitItem);
    }

    public static function getSubscribedServices(): array
    {
        return [
            'oro_product.provider.product_kit_item_unit_precision' => ProductKitItemUnitPrecisionProvider::class,
        ];
    }

    private function getProductKitItemUnitPrecisionProvider(): ProductKitItemUnitPrecisionProvider
    {
        if (null === $this->kitItemUnitPrecisionProvider) {
            $this->kitItemUnitPrecisionProvider = $this->container->get(
                'oro_product.provider.product_kit_item_unit_precision'
            );
        }

        return $this->kitItemUnitPrecisionProvider;
    }
}

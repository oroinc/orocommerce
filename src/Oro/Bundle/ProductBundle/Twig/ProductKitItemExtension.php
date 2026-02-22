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
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ProductKitItemUnitPrecisionProvider::class
        ];
    }

    private function getProductKitItemUnitPrecisionProvider(): ProductKitItemUnitPrecisionProvider
    {
        return $this->container->get(ProductKitItemUnitPrecisionProvider::class);
    }
}

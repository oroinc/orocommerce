<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The base class for product price demo fixtures.
 */
abstract class AbstractLoadProductPriceDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    private array $products = [];
    private array $productUnis = [];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadPriceAttributePriceListDemoData::class,
            LoadProductDemoData::class,
            LoadProductUnitPrecisionDemoData::class
        ];
    }

    protected function getFileLocator(): FileLocatorInterface
    {
        return $this->container->get('file_locator');
    }

    protected function getProductBySku(ObjectManager $manager, string $sku): ?Product
    {
        if (!\array_key_exists($sku, $this->products)) {
            $this->products[$sku] = $manager->getRepository(Product::class)->findOneBy(['sku' => $sku]);
        }

        return $this->products[$sku];
    }

    protected function getProductUnit(ObjectManager $manager, string $code): ?ProductUnit
    {
        if (!\array_key_exists($code, $this->productUnis)) {
            $this->productUnis[$code] = $manager->getRepository(ProductUnit::class)->find($code);
        }

        return $this->productUnis[$code];
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;

abstract class AbstractLoadProductPriceDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $products = [];

    /**
     * @var array
     */
    protected $productUnis = [];

    /**
     * @var array
     */
    protected $priceLists = [];

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadPriceAttributePriceListDemoData::class,
            LoadProductDemoData::class,
            LoadProductUnitPrecisionDemoData::class
        ];
    }

    /**
     * {@inheritdoc}
     * @param EntityManager $manager
     */
    abstract public function load(ObjectManager $manager);

    /**
     * @param EntityManager $manager
     * @param string $sku
     * @return Product|null
     */
    protected function getProductBySku(EntityManager $manager, $sku)
    {
        if (!array_key_exists($sku, $this->products)) {
            $this->products[$sku] = $manager->getRepository('OroProductBundle:Product')->findOneBy(['sku' => $sku]);
        }

        return $this->products[$sku];
    }

    /**
     * @param EntityManager $manager
     * @param string $code
     * @return ProductUnit|null
     */
    protected function getProductUnit(EntityManager $manager, $code)
    {
        if (!array_key_exists($code, $this->productUnis)) {
            $this->productUnis[$code] = $manager->getRepository('OroProductBundle:ProductUnit')->find($code);
        }

        return $this->productUnis[$code];
    }
}

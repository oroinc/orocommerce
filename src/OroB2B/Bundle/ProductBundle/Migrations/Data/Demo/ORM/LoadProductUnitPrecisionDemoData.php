<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class LoadProductUnitPrecisionDemoData extends AbstractFixture implements
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
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/add_product_precisions.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $product = $this->getProductBySku($manager, $row['sku']);
            $productUnit = $this->getProductUnit($manager, $row['unit']);

            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision
                ->setProduct($product)
                ->setUnit($productUnit)
                ->setPrecision((int)$row['precision'])
                ->setConversionRate((float)$row['conversion_rate'])
                ->setSell((bool)$row['sell']);

            $manager->persist($productUnitPrecision);
        }

        fclose($handler);

        $manager->flush();
    }

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

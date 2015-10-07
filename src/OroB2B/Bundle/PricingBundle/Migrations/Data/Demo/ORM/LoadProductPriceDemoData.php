<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class LoadProductPriceDemoData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
            'OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData',
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            'OroB2B\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BPricingBundle/Migrations/Data/Demo/ORM/data/product_prices.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $product = $this->getProductBySku($manager, $row['sku']);
            $productUnit = $this->getProductUnit($manager, $row['unitCode']);
            $priceList = $this->getPriceList($manager, $row['priceListName']);
            $price = Price::create($row['price'], $row['currency']);

            $productPrice = new ProductPrice();
            $productPrice
                ->setProduct($product)
                ->setUnit($productUnit)
                ->setPriceList($priceList)
                ->setQuantity($row['quantity'])
                ->setPrice($price);

            $manager->persist($productPrice);

            $productPrice10 = clone $productPrice;
            $productPrice10
                ->setQuantity($row['quantity'] * 10)
                ->setPrice($price->setValue($price->getValue() * 0.95));
            $manager->persist($productPrice10);

            $productPrice20 = clone $productPrice;
            $productPrice20
                ->setQuantity($row['quantity'] * 20)
                ->setPrice($price->setValue($price->getValue() * 0.9));
            $manager->persist($productPrice20);

            $productPrice50 = clone $productPrice;
            $productPrice50
                ->setQuantity($row['quantity'] * 50)
                ->setPrice($price->setValue($price->getValue() * 0.85));
            $manager->persist($productPrice50);

            $productPrice100 = clone $productPrice;
            $productPrice100
                ->setQuantity($row['quantity'] * 100)
                ->setPrice($price->setValue($price->getValue() * 0.8));
            $manager->persist($productPrice100);
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
            $this->products[$sku] = $manager->getRepository('OroB2BProductBundle:Product')->findOneBy(['sku' => $sku]);
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
            $this->productUnis[$code] = $manager->getRepository('OroB2BProductBundle:ProductUnit')->find($code);
        }

        return $this->productUnis[$code];
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return PriceList|null
     */
    protected function getPriceList(EntityManager $manager, $name)
    {
        if (!array_key_exists($name, $this->priceLists)) {
            $this->priceLists[$name] = $manager->getRepository('OroB2BPricingBundle:PriceList')
                ->findOneBy(['name' => $name]);
        }

        return $this->priceLists[$name];
    }
}

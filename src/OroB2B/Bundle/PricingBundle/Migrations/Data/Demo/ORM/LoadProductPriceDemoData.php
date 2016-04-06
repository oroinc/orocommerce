<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;

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
        $filePath = $locator->locate('@OroB2BProductBundle/Migrations/Data/Demo/ORM/data/products.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $priceLists = [
            'Default Price List' => [
                'currencies' => ['USD'], // 'EUR'], // intentionally no prices in the default list in the sample data
                'discount' => 0,
            ],
            'Wholesale Price List' => [
                'currencies' => ['USD', 'EUR'],
                'discount' => 0.1,
            ],
            'Partner C Custom Price List' => [
                'currencies' => ['USD'],
                'discount' => 0.2,
            ],
        ];

        $xRate = [
            'USD' => 1.00,
            'EUR' => 0.89,
        ];

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $product = $this->getProductBySku($manager, $row['sku']);
            $productUnit = $this->getProductUnit($manager, $row['unit']);
            foreach ($priceLists as $listName => $listOptions) {
                $priceList = $this->getPriceList($manager, $listName);
                foreach ($listOptions['currencies'] as $currency) {
                    $amount = round(
                        $row['price'] * (1 - $listOptions['discount']) * $xRate[$currency],
                        2
                    );
                    $price = Price::create($amount, $currency);

                    $productPrice = new ProductPrice();
                    $productPrice
                        ->setProduct($product)
                        ->setUnit($productUnit)
                        ->setPriceList($priceList)
                        ->setQuantity(1)
                        ->setPrice($price);

                    $manager->persist($productPrice);

                    $this->createPriceTiers($manager, $productPrice, $price);
                }
            }
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param ProductPrice $productPrice
     * @param Price $unitPrice
     */
    protected function createPriceTiers(ObjectManager $manager, ProductPrice $productPrice, Price $unitPrice)
    {
        $tiers = [
            10  => 0.05,
            20  => 0.10,
            50  => 0.15,
            100 => 0.20,
        ];

        foreach ($tiers as $qty => $discount) {
            $price = clone $productPrice;
            $price
                ->setQuantity($qty)
                ->setPrice($unitPrice->setValue(round($unitPrice->getValue() * (1 - $discount), 2)));
            $manager->persist($price);
        }
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

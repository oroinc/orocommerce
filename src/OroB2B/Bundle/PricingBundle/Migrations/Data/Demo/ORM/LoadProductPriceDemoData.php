<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class LoadProductPriceDemoData extends AbstractLoadProductPriceDemoData
{
    /**
     * {@inheritdoc}
     * @param EntityManager $manager
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

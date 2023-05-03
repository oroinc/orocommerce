<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;

/**
 * Loading product price demo data.
 */
class LoadProductPriceDemoData extends AbstractLoadProductPriceDemoData
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(
            parent::getDependencies(),
            [
                LoadPriceListToCustomerGroupDemoData::class,
                LoadPriceListDemoData::class,
            ]
        );
    }

    /**
     * {@inheritdoc}
     * @param EntityManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $priceLists = [
            'Default Price List' => [
                'discount' => 0,
            ],
            'Wholesale Price List' => [
                'discount' => 0.1,
            ],
            'Partner C Custom Price List' => [
                'discount' => 0.2,
            ],
        ];

        $priceManager = $this->container->get('oro_pricing.manager.price_manager');
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $product = $this->getProductBySku($manager, $row['sku']);
            $productUnit = $this->getProductUnit($manager, $row['unit']);
            foreach ($priceLists as $listName => $listOptions) {
                $priceList = $this->getPriceList($manager, $listName);
                foreach ($priceList->getCurrencies() as $currency) {
                    $amount = round(
                        (float)$row['price'] * (1 - (float)$listOptions['discount']),
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

                    $priceManager->persist($productPrice);

                    $this->createPriceTiers($priceManager, $productPrice, $price);
                }
            }
        }

        fclose($handler);

        $manager->flush();
    }

    protected function createPriceTiers(
        PriceManager $priceManager,
        ProductPrice $productPrice,
        Price $unitPrice
    ) {
        $tiers = [
            10  => 0.05,
            20  => 0.10,
            50  => 0.15,
            100 => 0.20,
        ];

        foreach ($tiers as $qty => $discount) {
            $price = clone $productPrice;
            $currentPrice = clone $unitPrice;
            $price
                ->setQuantity($qty)
                ->setPrice($currentPrice->setValue(round($unitPrice->getValue() * (1 - $discount), 2)));
            $priceManager->persist($price);
        }
    }

    protected function getPriceList(EntityManagerInterface $manager, string $name): ?PriceList
    {
        if (!array_key_exists($name, $this->priceLists)) {
            $this->priceLists[$name] = $manager->getRepository('OroPricingBundle:PriceList')
                ->findOneBy(['name' => $name]);
        }

        return $this->priceLists[$name];
    }
}

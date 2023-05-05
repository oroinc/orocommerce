<?php

namespace Oro\Bundle\FixedProductShippingBundle\Migrations\Data\Demo\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FixedProductShippingBundle\Migrations\Data\ORM\LoadPriceAttributePriceListData;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\AbstractLoadProductPriceDemoData;

/**
 * Loads Shipping Cost price attributes demo data
 */
class LoadPriceAttributeProductPriceDemoData extends AbstractLoadProductPriceDemoData
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $priceAttributePriceList = $this->getPriceAttribute($manager);
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $product = $this->getProductBySku($manager, $row['sku']);
            if ($product->getId() > 21) {
                continue;
            }

            $amount = round(mt_rand(1, 100) / 10, 2);
            $productUnit = $this->getProductUnit($manager, $row['unit']);
            foreach ($priceAttributePriceList->getCurrencies() as $currency) {
                $price = Price::create($amount, $currency);

                $priceAttributeProductPrice = new PriceAttributeProductPrice();
                $priceAttributeProductPrice
                    ->setProduct($product)
                    ->setUnit($productUnit)
                    ->setPriceList($priceAttributePriceList)
                    ->setQuantity(1)
                    ->setPrice($price);

                $manager->persist($priceAttributeProductPrice);
            }
        }

        fclose($handler);

        $manager->flush();
    }

    private function getPriceAttribute(EntityManagerInterface $manager): PriceAttributePriceList
    {
        return $manager->getRepository('OroPricingBundle:PriceAttributePriceList')
            ->findOneBy(['name' => LoadPriceAttributePriceListData::SHIPPING_COST_NAME]);
    }
}

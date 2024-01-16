<?php

namespace Oro\Bundle\FixedProductShippingBundle\Migrations\Data\Demo\ORM;

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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $priceAttributePriceList = $this->getPriceAttribute($manager);
        foreach ($this->getProducts() as $row) {
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

        $manager->flush();
    }

    protected function getProducts(): \Iterator
    {
        $filePath = $this->getFileLocator()
            ->locate('@OroProductBundle/Migrations/Data/Demo/ORM/data/products.csv');
        if (\is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            yield array_combine($headers, array_values($data));
        }

        fclose($handler);
    }

    private function getPriceAttribute(ObjectManager $manager): PriceAttributePriceList
    {
        return $manager->getRepository(PriceAttributePriceList::class)
            ->findOneBy(['name' => LoadPriceAttributePriceListData::SHIPPING_COST_NAME]);
    }
}

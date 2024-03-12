<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Loads MSRP and MAP price attributes demo data
 */
class LoadPriceAttributeProductPriceDemoData extends AbstractLoadProductPriceDemoData
{
    private array $priceLists = [];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return array_merge(
            parent::getDependencies(),
            [
                LoadPriceAttributePriceListDemoData::class,
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $priceAttributes = [
            'MSRP',
            'MAP',
        ];

        foreach ($this->getProducts() as $row) {
            $product = $this->getProductBySku($manager, $row['sku']);
            $productUnit = $this->getProductUnit($manager, $row['unit']);
            foreach ($priceAttributes as $attributeName) {
                $priceAttributePriceList = $this->getPriceAttribute($manager, $attributeName);
                foreach ($priceAttributePriceList->getCurrencies() as $currency) {
                    $amount = round((float) $row[$attributeName], 2);
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
        }

        $manager->flush();
    }

    protected function getFileLocator(): FileLocatorInterface
    {
        return $this->container->get('file_locator');
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

    protected function getPriceAttribute(ObjectManager $manager, string $name): ?PriceAttributePriceList
    {
        if (!\array_key_exists($name, $this->priceLists)) {
            $this->priceLists[$name] = $manager->getRepository(PriceAttributePriceList::class)
                ->findOneBy(['name' => $name]);
        }

        return $this->priceLists[$name];
    }
}

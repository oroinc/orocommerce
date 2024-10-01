<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;

/**
 * Loads demo data for price lists to websites relations
 */
class LoadPriceListToWebsiteDemoData extends LoadBasePriceListRelationDemoData
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadWebsiteData::class, LoadPriceListDemoData::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $filePath = $this->getFileLocator()
            ->locate('@OroPricingBundle/Migrations/Data/Demo/ORM/data/price_lists_to_website.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        $website = $this->getWebsiteByName($manager, LoadWebsiteData::DEFAULT_WEBSITE_NAME);
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $priceList = $this->getPriceListByName($manager, $row['priceList']);

            $priceListToCustomer = new PriceListToWebsite();
            $priceListToCustomer->setWebsite($website)
                ->setPriceList($priceList)
                ->setSortOrder($row['sort_order'])
                ->setMergeAllowed((bool)$row['mergeAllowed']);
            $manager->persist($priceListToCustomer);
        }

        fclose($handler);

        $manager->flush();
    }
}

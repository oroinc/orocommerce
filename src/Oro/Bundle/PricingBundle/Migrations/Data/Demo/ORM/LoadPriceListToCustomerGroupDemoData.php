<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;

/**
 * Loading price list for customer group demo data.
 */
class LoadPriceListToCustomerGroupDemoData extends LoadBasePriceListRelationDemoData
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [LoadWebsiteData::class]);
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $filePath = $this->getFileLocator()
            ->locate('@OroPricingBundle/Migrations/Data/Demo/ORM/data/price_lists_to_customer_group.csv');
        if (\is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        $website = $this->getWebsiteByName($manager, LoadWebsiteData::DEFAULT_WEBSITE_NAME);
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $customer = $this->getCustomerGroupByName($manager, $row['customerGroup']);
            $priceList = $this->getPriceListByName($manager, $row['priceList']);

            $priceListToCustomerGroup = new PriceListToCustomerGroup();
            $priceListToCustomerGroup->setCustomerGroup($customer)
                ->setPriceList($priceList)
                ->setWebsite($website)
                ->setSortOrder($row['sort_order'])
                ->setMergeAllowed((bool)$row['mergeAllowed']);

            $manager->persist($priceListToCustomerGroup);
        }

        fclose($handler);

        $manager->flush();
    }

    private function getCustomerGroupByName(ObjectManager $manager, string $name): CustomerGroup
    {
        $website = $manager->getRepository(CustomerGroup::class)->findOneBy(['name' => $name]);
        if (!$website) {
            throw new \LogicException(sprintf('There is no customer group with name "%s" .', $name));
        }

        return $website;
    }
}

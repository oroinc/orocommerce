<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;

class LoadPriceListToCustomerGroupDemoData extends LoadBasePriceListRelationDemoData
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator
            ->locate('@OroPricingBundle/Migrations/Data/Demo/ORM/data/price_lists_to_customer_group.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        /** @var EntityManager $manager */
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
                ->setMergeAllowed((boolean)$row['mergeAllowed']);

            $manager->persist($priceListToCustomerGroup);
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return CustomerGroup
     */
    protected function getCustomerGroupByName(EntityManager $manager, $name)
    {
        $website = $manager->getRepository('OroCustomerBundle:CustomerGroup')->findOneBy(['name' => $name]);

        if (!$website) {
            throw new \LogicException(sprintf('There is no customer group with name "%s" .', $name));
        }

        return $website;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(parent::getDependencies(), [LoadWebsiteData::class]);
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;

/**
 * Loading price list for customer demo data.
 */
class LoadPriceListToCustomerDemoData extends LoadBasePriceListRelationDemoData
{
    /**
     * @var EntityRepository
     */
    protected $customerRepository;

    /**
     * @var Customer[]
     */
    protected $customers = [];

    /**
     * {@inheritdoc}
     * @param EntityManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroPricingBundle/Migrations/Data/Demo/ORM/data/price_lists_to_customer.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        /** @var EntityManager $manager */
        $website = $this->getWebsiteByName($manager, LoadWebsiteData::DEFAULT_WEBSITE_NAME);
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $customer = $this->getCustomerByName($manager, $row['customer']);
            $priceList = $this->getPriceListByName($manager, $row['priceList']);

            $priceListToCustomer = new PriceListToCustomer();
            $priceListToCustomer->setCustomer($customer)
                ->setPriceList($priceList)
                ->setWebsite($website)
                ->setSortOrder($row['sort_order'])
                ->setMergeAllowed((boolean)$row['mergeAllowed']);

            $manager->persist($priceListToCustomer);
        }

        fclose($handler);

        $manager->flush();
    }

    protected function getCustomerByName(EntityManagerInterface $manager, string $name): Customer
    {
        if (!array_key_exists($name, $this->customers)) {
            $customer = $this->getCustomerRepository($manager)->findOneBy(['name' => $name]);

            if (!$customer) {
                throw new \LogicException(sprintf('There is no customer with name "%s" .', $name));
            }
            $this->customers[$name] = $customer;
        }

        return $this->customers[$name];
    }

    protected function getCustomerRepository(EntityManagerInterface $manager): EntityRepository
    {
        if ($this->customerRepository === null) {
            $this->customerRepository = $manager->getRepository('OroCustomerBundle:Customer');
        }

        return $this->customerRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(parent::getDependencies(), [LoadWebsiteData::class]);
    }
}

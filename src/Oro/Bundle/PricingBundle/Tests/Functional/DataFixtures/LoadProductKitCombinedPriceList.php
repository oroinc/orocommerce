<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadProductKitCombinedPriceList extends AbstractFixture implements DependentFixtureInterface
{
    protected static array $data = [
        'priceListsToCustomerGroups' => [
            [
                'website' => LoadWebsiteData::WEBSITE1,
                'priceList' => '1f',
                'group' => LoadGroups::ANONYMOUS_GROUP,
                'reference' => 'kit_combined_price_list_to_anonymous_group',
            ],
        ],
        'priceListsToCustomers' => [
            [
                'website' => LoadWebsiteData::WEBSITE1,
                'priceList' => '1f',
                'customer' => LoadCustomers::CUSTOMER_LEVEL_1,
                'reference' => 'kit_combined_price_list_to_customer_1',
            ],
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadCombinedPriceLists::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach (self::$data['priceListsToCustomerGroups'] as $data) {
            $this->loadCombinedPriceListToCustomerGroup($manager, $data);
        }

        foreach (self::$data['priceListsToCustomers'] as $data) {
            $this->loadCombinedPriceListToCustomer($manager, $data);
        }

        $manager->flush();
    }

    protected function loadCombinedPriceListToCustomer(ObjectManager $manager, array $data): void
    {
        $customer = $this->getReference($data['customer']);
        $website = $this->getReference($data['website']);
        $priceList = $this->getReference($data['priceList']);

        $priceListToCustomer = new CombinedPriceListToCustomer();
        $priceListToCustomer->setCustomer($customer);
        $priceListToCustomer->setWebsite($website);
        $priceListToCustomer->setPriceList($priceList);

        $manager->persist($priceListToCustomer);
        $this->setReference($data['reference'], $priceListToCustomer);
    }

    protected function loadCombinedPriceListToCustomerGroup(ObjectManager $manager, array $data): void
    {
        $customerGroup = $this->getReference($data['group']);
        $website = $this->getReference($data['website']);
        $priceList = $this->getReference($data['priceList']);

        $priceListToCustomerGroup = new CombinedPriceListToCustomerGroup();
        $priceListToCustomerGroup->setCustomerGroup($customerGroup);
        $priceListToCustomerGroup->setWebsite($website);
        $priceListToCustomerGroup->setPriceList($priceList);

        $manager->persist($priceListToCustomerGroup);
        $this->setReference($data['reference'], $priceListToCustomerGroup);
    }
}

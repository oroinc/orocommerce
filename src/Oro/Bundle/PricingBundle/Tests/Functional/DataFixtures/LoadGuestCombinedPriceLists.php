<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Migrations\Data\ORM\LoadAnonymousCustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerVisitors;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadGuestCombinedPriceLists extends AbstractCombinedPriceListsFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => '4t_5t',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [
                [
                    'group' => 'customer_group.anonymous',
                    'website' => LoadWebsiteData::WEBSITE1,
                ],
            ],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_4',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_5',
                    'mergeAllowed' => true,
                ],
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $group = $manager->getRepository(CustomerGroup::class)
            ->findOneByName(LoadAnonymousCustomerGroup::GROUP_NAME_NON_AUTHENTICATED);

        $this->setReference('customer_group.anonymous', $group);

        parent::load($manager);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPriceLists::class,
            LoadWebsiteData::class,
            LoadCustomerVisitors::class,
        ];
    }
}

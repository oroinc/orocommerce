<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;

class LoadPriceListRelationsForTriggers extends LoadPriceListRelations
{
    const PRICE_LIST_TO_CUSTOMER_GROUP_6 = 'price_list_2_US_customer_group1';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->data['US']['priceListsToCustomerGroups']['customer_group.group1'][] = [
            'reference' => self::PRICE_LIST_TO_CUSTOMER_GROUP_6,
            'priceList' => 'price_list_4',
            'sort_order' => 10,
            'mergeAllowed' => false,
        ];

        parent::load($manager);
    }
}

<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

class LoadCustomerGroupDemoData extends AbstractFixture
{
    const ACCOUNT_GROUP_REFERENCE_PREFIX = 'customer_group_demo_data';

    /**
     * @var array
     */
    protected $customerGroups = [
        'All Customers',
        'Wholesale Customers',
        'Partners'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->customerGroups as $groupName) {
            $customerGroup = new CustomerGroup();
            $customerGroup->setName($groupName);
            $manager->persist($customerGroup);
            $this->addReference(static::ACCOUNT_GROUP_REFERENCE_PREFIX . $customerGroup->getName(), $customerGroup);
        }

        $manager->flush();
    }
}

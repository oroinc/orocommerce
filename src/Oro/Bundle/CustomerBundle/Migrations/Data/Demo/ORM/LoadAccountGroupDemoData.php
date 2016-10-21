<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;

class LoadAccountGroupDemoData extends AbstractFixture
{
    const ACCOUNT_GROUP_REFERENCE_PREFIX = 'account_group_demo_data';

    /**
     * @var array
     */
    protected $accountGroups = [
        'All Customers',
        'Wholesale Accounts',
        'Partners'
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->accountGroups as $groupName) {
            $accountGroup = new AccountGroup();
            $accountGroup->setName($groupName);
            $manager->persist($accountGroup);
            $this->addReference(static::ACCOUNT_GROUP_REFERENCE_PREFIX . $accountGroup->getName(), $accountGroup);
        }

        $manager->flush();
    }
}

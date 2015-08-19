<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class LoadAccountGroupDemoData extends AbstractFixture
{
    const ACCOUNT_GROUP_REFERENCE_PREFIX = 'account_group_demo_data';

    /**
     * @var array
     */
    protected $accountGroups = [
        'Root',
        'First',
        'Second'
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

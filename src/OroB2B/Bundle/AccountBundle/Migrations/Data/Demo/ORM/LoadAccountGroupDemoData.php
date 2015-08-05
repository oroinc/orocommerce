<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

class LoadAccountGroupDemoData extends AbstractFixture
{
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
        }

        $manager->flush();
    }
}

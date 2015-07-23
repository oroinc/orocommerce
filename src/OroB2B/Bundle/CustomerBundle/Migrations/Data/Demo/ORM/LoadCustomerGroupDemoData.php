<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

class LoadCustomerGroupDemoData extends AbstractFixture
{
    private function getData()
    {
        return [
            'Root',
            'First',
            'Second'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $groupName) {
            $customerGroup = new CustomerGroup();
            $customerGroup->setName($groupName);
            $manager->persist($customerGroup);
        }

        $manager->flush();
    }
}

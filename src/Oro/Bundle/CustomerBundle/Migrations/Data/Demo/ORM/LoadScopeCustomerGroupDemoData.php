<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadScopeCustomerGroupDemoData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const SCOPE_ACCOUNT_GROUP_REFERENCE_PREFIX = 'scope_customer_group_demo_data';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCustomerGroupDemoData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroups = $manager->getRepository('OroCustomerBundle:CustomerGroup')->findAll();
        foreach ($customerGroups as $customerGroup) {
            $scope = new Scope();
            $scope->setCustomerGroup($customerGroup);
            $this->addReference(static::SCOPE_ACCOUNT_GROUP_REFERENCE_PREFIX . $customerGroup->getName(), $scope);
            $manager->persist($scope);
        }

        $manager->flush();
    }
}

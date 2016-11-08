<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadScopeAccountGroupDemoData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const SCOPE_ACCOUNT_GROUP_REFERENCE_PREFIX = 'scope_account_group_demo_data';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAccountGroupDemoData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroups = $manager->getRepository('OroCustomerBundle:AccountGroup')->findAll();
        foreach ($accountGroups as $accountGroup) {
            $scope = new Scope();
            $scope->setAccountGroup($accountGroup);
            $this->addReference(static::SCOPE_ACCOUNT_GROUP_REFERENCE_PREFIX . $accountGroup->getName(), $scope);
            $manager->persist($scope);
        }

        $manager->flush();
    }
}

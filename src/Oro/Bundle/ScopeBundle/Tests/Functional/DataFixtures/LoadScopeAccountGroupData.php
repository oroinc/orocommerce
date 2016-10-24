<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;

use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadScopeAccountGroupData extends AbstractFixture implements DependentFixtureInterface
{
    const SCOPE_PREFIX = 'scope_account_group';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadGroups::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference(LoadGroups::GROUP1);

        $scope = new Scope();
        $scope->setAccountGroup($accountGroup);
        $manager->persist($scope);
        $this->addReference(static::SCOPE_PREFIX, $scope);
        $manager->flush();
    }
}

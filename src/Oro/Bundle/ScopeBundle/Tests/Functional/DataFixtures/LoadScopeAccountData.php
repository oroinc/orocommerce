<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;

use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadScopeAccountData extends AbstractFixture implements DependentFixtureInterface
{
    const SCOPE_PREFIX = 'scope_account';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadAccounts::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Account $account */
        $account = $this->getReference(LoadAccounts::DEFAULT_ACCOUNT_NAME);

        $scope = new Scope();
        $scope->setAccount($account);
        $manager->persist($scope);
        $this->addReference(static::SCOPE_PREFIX, $scope);
        $manager->flush();
    }
}

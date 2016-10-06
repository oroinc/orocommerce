<?php

namespace Oro\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadScopeAccountDemoData extends AbstractFixture implements FixtureInterface, DependentFixtureInterface
{
    const SCOPE_ACCOUNT_REFERENCE_PREFIX = 'scope_account_demo_data';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadAccountDemoData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var \Oro\Bundle\AccountBundle\Entity\Account $account */
        $accounts = $manager->getRepository('OroAccountBundle:Account')->findAll();
        foreach ($accounts as $account) {
            $scope = new Scope();
            $scope->setAccount($account);
            $this->addReference(static::SCOPE_ACCOUNT_REFERENCE_PREFIX . $account->getName(), $scope);
            $manager->persist($scope);
        }

        $manager->flush();
    }
}

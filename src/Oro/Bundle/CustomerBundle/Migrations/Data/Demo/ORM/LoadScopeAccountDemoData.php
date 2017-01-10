<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

use Oro\Bundle\CustomerBundle\Entity\Customer;
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
        /** @var Customer $account */
        $accounts = $manager->getRepository('OroCustomerBundle:Customer')->findAll();
        foreach ($accounts as $account) {
            $scope = new Scope();
            $scope->setAccount($account);
            $this->addReference(static::SCOPE_ACCOUNT_REFERENCE_PREFIX . $account->getName(), $scope);
            $manager->persist($scope);
        }

        $manager->flush();
    }
}

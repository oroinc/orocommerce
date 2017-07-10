<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadSlugScopesData extends AbstractFixture implements DependentFixtureInterface
{
    const SCOPE_KEY = 'slug_scope';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        /** @var Customer $secondCustomer */
        $secondCustomer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1);

        $scope = $this->createScopeWithCustomer($manager, $customer);
        $this->addReference(self::SCOPE_KEY, $scope);
        $this->createScopeWithCustomer($manager, $secondCustomer);

        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_USER);
        $slug->addScope($scope);

        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_TEST_DUPLICATE_URL);
        $slug->addScope($scope);

        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_PAGE);
        $slug->addScope($scope);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSlugsData::class,
            LoadCustomers::class
        ];
    }

    /**
     * @param ObjectManager $manager
     * @param Customer $customer
     * @return Scope
     */
    private function createScopeWithCustomer(ObjectManager $manager, Customer $customer)
    {
        $scope = new Scope();
        if (method_exists($scope, 'setCustomer')) {
            $scope->setCustomer($customer);
        }
        $manager->persist($scope);

        return $scope;
    }
}

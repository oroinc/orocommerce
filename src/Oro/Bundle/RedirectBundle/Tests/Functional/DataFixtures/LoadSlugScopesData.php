<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class LoadSlugScopesData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var CustomerUser $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);

        $scope = new Scope();
        if (method_exists($scope, 'setCustomer')) {
            $scope->setCustomer($customer);
        }
        $manager->persist($scope);

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
}

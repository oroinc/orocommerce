<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadSlugScopesData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    const SCOPE_KEY = 'slug_scope';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Customer $customer */
        $customer = $this->getReference(LoadCustomers::DEFAULT_ACCOUNT_NAME);
        /** @var Customer $secondCustomer */
        $secondCustomer = $this->getReference(LoadCustomers::CUSTOMER_LEVEL_1_1);

        $scope = $this->createScopeWithCustomer($customer);
        $this->addReference(self::SCOPE_KEY, $scope);
        $this->createScopeWithCustomer($secondCustomer);

        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_USER);
        $slug->addScope($scope);

        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_TEST_URL);
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
     * @param Customer $customer
     * @return Scope
     */
    protected function createScopeWithCustomer(Customer $customer)
    {
        return $this->container->get('oro_scope.scope_manager')
            ->findOrCreate('web_content', ['customer' => $customer]);
    }
}

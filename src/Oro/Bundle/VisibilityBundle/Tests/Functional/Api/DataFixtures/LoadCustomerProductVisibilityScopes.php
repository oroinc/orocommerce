<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadCustomerProductVisibilityScopes extends AbstractFixture implements
    InitialFixtureInterface,
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    public function getDependencies()
    {
        return [LoadCustomers::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $visibilityProvider = $this->container->get('oro_visibility.provider.visibility_scope_provider');
        $websiteManager = $this->container->get('oro_website.manager');
        $website = $websiteManager->getDefaultWebsite();

        $scope1 = $visibilityProvider->getCustomerProductVisibilityScope(
            $this->getReference('customer.orphan'),
            $website
        );
        $this->addReference('scope_1', $scope1);

        $scope2 = $visibilityProvider->getCustomerProductVisibilityScope(
            $this->getReference('customer.level_1_1'),
            $website
        );
        $this->addReference('scope_2', $scope2);

        $scope2 = $visibilityProvider->getCustomerProductVisibilityScope(
            $this->getReference('customer.level_1.1'),
            $website
        );
        $this->addReference('scope_3', $scope2);
    }
}

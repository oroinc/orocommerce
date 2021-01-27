<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\InitialFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadProductVisibilityScope extends AbstractFixture implements InitialFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $visibilityProvider = $this->container->get('oro_visibility.provider.visibility_scope_provider');
        $websiteManager = $this->container->get('oro_website.manager');

        $scope = $visibilityProvider->getProductVisibilityScope($websiteManager->getDefaultWebsite());
        $this->addReference('scope', $scope);
    }
}

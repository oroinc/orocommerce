<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Enables Consents Feature
 */
class EnableConsentFeature extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $configManager = $this->container->get('oro_config.global');
        $configManager->set(Configuration::getConfigKey(Configuration::CONSENT_FEATURE_ENABLED), true);

        $configManager->flush();
    }
}

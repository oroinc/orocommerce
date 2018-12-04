<?php

namespace Oro\Bundle\ConsentBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\ContactUsBundle\Entity\ContactReason;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates additional contact reason for consents and saves its id to configuration
 */
class LoadContactReasonData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $reasonLabel = 'General Data Protection Regulation details';

        $reason = new ContactReason($reasonLabel);
        $manager->persist($reason);
        $manager->flush();

        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_config.global');

        $configKey = Configuration::getConfigKey(Configuration::CONSENT_CONTACT_REASON);
        $configManager->set($configKey, $reason->getId());
        $configManager->flush();
    }
}

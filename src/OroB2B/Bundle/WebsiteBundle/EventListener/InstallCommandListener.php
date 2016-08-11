<?php

namespace OroB2B\Bundle\WebsiteBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class InstallCommandListener
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param ConfigManager $configManager
     * @param RegistryInterface $registry
     */
    public function __construct(ConfigManager $configManager, RegistryInterface $registry)
    {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onTerminate(ConsoleTerminateEvent $event)
    {
        if ('oro:install' === $event->getCommand()->getName()) {
            $repo = $this->registry->getRepository(Website::class);
            $website = $repo->getDefaultWebsite();
            $url = $this->configManager->get('oro_ui.application_url');
            $this->configManager->set('oro_b2b_website.url', $url, $website->getId());
            $this->configManager->set('oro_b2b_website.secure_url', $url, $website->getId());
            $this->configManager->flush();
        }
    }
}

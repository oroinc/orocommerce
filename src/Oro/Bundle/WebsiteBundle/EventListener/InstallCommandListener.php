<?php

namespace Oro\Bundle\WebsiteBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InstallerBundle\Command\InstallCommand;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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
        $command = $event->getCommand();
        if ($command instanceof InstallCommand) {
            try {
                /** @var WebsiteRepository $repo */
                $repo = $this->registry->getRepository(Website::class);
                $website = $repo->getDefaultWebsite();
                $url = $this->configManager->get('oro_ui.application_url');
                $this->configManager->set('oro_b2b_website.url', $url);
                $this->configManager->set('oro_b2b_website.secure_url', $url);
                $this->configManager->flush($website);
            } catch (\Exception $e) {
                //do nothing in case when application not installed and table not exists
            }
        }
    }
}

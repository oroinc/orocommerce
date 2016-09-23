<?php

namespace Oro\Bundle\WebsiteBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InstallerBundle\Command\InstallCommand;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

class InstallCommandListener
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param ConsoleTerminateEvent $event
     */
    public function onTerminate(ConsoleTerminateEvent $event)
    {
        $command = $event->getCommand();
        if ($command instanceof InstallCommand) {
            try {
                $url = $this->configManager->get('oro_ui.application_url');
                $this->configManager->set('oro_website.url', $url);
                $this->configManager->set('oro_website.secure_url', $url);
                $this->configManager->flush();
            } catch (\Exception $e) {
                //do nothing in case when application not installed and table not exists
            }
        }
    }
}

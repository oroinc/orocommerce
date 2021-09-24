<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Oro\Bundle\CMSBundle\Command\SanitizeWysiwygStyleFieldsCommand;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\DependencyInjection\OroCMSExtension;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Adds a notice about oro:cms:wysiwyg:sanitize:styles command to the oro:platform:update output.
 */
class SanitizeWysiwygNoticeOnPlatformUpdateListener
{
    private const CONFIG_FLAG = OroCMSExtension::ALIAS . '.' . Configuration::IS_SANITIZE_WYSIWYG_NOTICE_SHOWN;

    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if (!$event->getCommand() instanceof PlatformUpdateCommand ||
            $event->getExitCode() !== 0 ||
            !$event->getInput()->getOption('force') ||
            $this->configManager->get(self::CONFIG_FLAG)) {
            return;
        }

        $symfonyStyle = new SymfonyStyle($event->getInput(), $event->getOutput());
        $symfonyStyle->note(
            sprintf(
                'Please run "%s" Symfony command to check if WYSIWYG style fields contain unsafe content',
                SanitizeWysiwygStyleFieldsCommand::getDefaultName()
            )
        );

        $this->configManager->set(self::CONFIG_FLAG, true);
        $this->configManager->flush();
    }
}

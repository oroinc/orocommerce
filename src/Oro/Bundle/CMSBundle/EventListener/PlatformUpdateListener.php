<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\EventListener;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Oro\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;

/**
 * Aims to fix backwards incompatible changes to the layout of CMS landing page
 * that was introduced in 5.0.7 release and broke the customizations.
 */
class PlatformUpdateListener
{
    private const BASE_VERSION = 'v1_11_1'; // 5.0.6
    private const CONFIG_PARAMETER = 'oro_cms.is_updated_after_507';

    private ApplicationState $applicationState;

    private ConfigManager $configManager;

    public function __construct(ApplicationState $applicationState, ConfigManager $configManager)
    {
        $this->applicationState = $applicationState;
        $this->configManager = $configManager;
    }

    public function onPreUp(PreMigrationEvent $event): void
    {
        if (!$this->applicationState->isInstalled()) {
            // Application not installed, no need to fix the BC break
            return;
        }

        if (!$event->isTableExist(CreateMigrationTableMigration::MIGRATION_TABLE)) {
            // Missing required table, cannot check the version
            return;
        }

        $version = $this->getLoadedVersion($event);

        if (!$version) {
            // Required bundle never being installed (update CRM => Commerce+CRM)
            return;
        }

        $config = $this->configManager->getInfo(self::CONFIG_PARAMETER);
        if ($config['createdAt'] ?? null) {
            // Config parameter already set
            return;
        }

        if (version_compare($version, self::BASE_VERSION, '<=')) {
            // Updating application from 5.0.6 or below
            $this->configManager->set(self::CONFIG_PARAMETER, false);
        } else {
            // Updating application from 5.0.7 or above
            $this->configManager->set(self::CONFIG_PARAMETER, true);
        }
        $this->configManager->flush();
    }

    private function getLoadedVersion(PreMigrationEvent $event): ?string
    {
        $data = $event->getData(
            sprintf(
                'select * from %s where id in (select max(id) from %s group by bundle) and bundle = :bundle',
                CreateMigrationTableMigration::MIGRATION_TABLE,
                CreateMigrationTableMigration::MIGRATION_TABLE
            ),
            [
                'bundle' => 'OroCMSBundle'
            ],
            [
                'bundle' => Types::STRING
            ],
        );

        return $data[0]['version'] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Migration\RemoveOrphanMainImageConfigMigration;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Oro\Bundle\MigrationBundle\Event\PreMigrationEvent;

/**
 * Removes orphan `mainImage` filed config when upgrading from 3.1.2 to latest
 */
class RemoveOrphanMainImageConfigListener
{
    protected bool $isApplicable = false;

    private ConfigManager $configManager;
    private ManagerRegistry $registry;

    public function __construct(ConfigManager $configManager, ManagerRegistry $registry)
    {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }

    public function onPreUp(PreMigrationEvent $event)
    {
        $version = $event->getLoadedVersion('OroCMSBundle');
        if ($version && version_compare($version, 'v1_7', '<')) {
            $this->isApplicable = true;
        }
    }

    public function onPostUp(PostMigrationEvent $event)
    {
        if ($this->isApplicable) {
            $event->addMigration(
                new RemoveOrphanMainImageConfigMigration($this->configManager, $this->registry)
            );
        }
    }
}

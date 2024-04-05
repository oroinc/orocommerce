<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadHomePageData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Loads the Homepage landing page
 * and sets it as a value for system configuration option {@see Configuration::HOME_PAGE}.
 */
class LoadHomePageData extends AbstractLoadHomePageData
{
    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        $this->setHomepageSystemConfiguration();
    }

    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCMSBundle/Migrations/Data/ORM/data/homepage.yml');
    }

    protected function getConfigManager(): ConfigManager
    {
        return $this->container->get('oro_config.global');
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Base class to load a Homepage Landing Page
 * and sets it as a value for system configuration option {@see Configuration::HOME_PAGE}.
 */
abstract class AbstractLoadHomePageData extends AbstractLoadPageData
{
    protected const HOMEPAGE_REFERENCE = 'homepage';

    protected function setHomepageSystemConfiguration(?Organization $organization = null): void
    {
        $configManager = $this->getConfigManager();

        $configManager->set(
            Configuration::getConfigKeyByName(Configuration::HOME_PAGE),
            $this->getReference(static::HOMEPAGE_REFERENCE)->getId(),
            $organization
        );
        $configManager->flush();
    }

    abstract protected function getConfigManager(): ConfigManager;
}

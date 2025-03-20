<?php

namespace Oro\Bundle\CommerceBundle\Migrations\Data\Demo\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalDefaultThemeConfigurationData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;

/**
 * Loads customer dashboards content block data and configures theme configuration for default theme
 */
class LoadDefaultCustomerDashboardContentBlocksDemoData extends LoadCustomerDashboardContentBlocksDemoData
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            ...parent::getDependencies(),
            LoadGlobalDefaultThemeConfigurationData::class,
            LoadCustomerDashboardContentBlocksDemoData::class
        ];
    }

    #[\Override]
    protected function getThemeConfigurations(ObjectManager $manager, Organization $organization): array
    {
        return $manager->getRepository(ThemeConfiguration::class)->findBy([
            'theme' => $this->getFrontendTheme(),
            'organization' => $organization
        ]);
    }

    #[\Override]
    protected function getFrontendTheme(): ?string
    {
        return 'default';
    }
}

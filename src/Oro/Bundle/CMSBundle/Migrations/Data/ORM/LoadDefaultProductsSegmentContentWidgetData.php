<?php

namespace Oro\Bundle\CMSBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalDefaultThemeConfigurationData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;

/**
 * Class to load "product_segment" content widget's data and configures theme configuration for default theme
 */
class LoadDefaultProductsSegmentContentWidgetData extends LoadProductsSegmentContentWidgetData
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            ...parent::getDependencies(),
            LoadProductsSegmentContentWidgetData::class,
            LoadGlobalDefaultThemeConfigurationData::class
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

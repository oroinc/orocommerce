<?php

namespace Oro\Bundle\CommerceBundle\Migrations\Data\ORM;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\CMSBundle\Migrations\Data\AbstractLoadContentWidgetData;
use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalThemeConfigurationData;

/**
 * Loads customer dashboards scorecard content widget data and configures theme configuration for active theme
 */
class LoadScorecardContentWidgetData extends AbstractLoadContentWidgetData
{
    public function getVersion(): string
    {
        return '1.0';
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            ...parent::getDependencies(),
            LoadGlobalThemeConfigurationData::class
        ];
    }

    #[\Override]
    protected function getFilePaths(): string
    {
        return $this->getFilePathsFromLocator('@OroCommerceBundle/Migrations/Data/ORM/data/scorecard_widgets.yml');
    }

    #[\Override]
    protected function updateContentWidget(ObjectManager $manager, ContentWidget $contentWidget, array $row): void
    {
    }

    #[\Override]
    protected function getFrontendTheme(): ?string
    {
        return null;
    }
}

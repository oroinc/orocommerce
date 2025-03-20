<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\FrontendBundle\Migrations\Data\ORM\LoadGlobalDefaultThemeConfigurationData;

/**
 * Loads promotional content block data and configures system config for organizations
 */
class LoadPromotionContentBlockData extends AbstractLoadPromotionContentBlockData
{
    #[\Override]
    public function getDependencies(): array
    {
        return [
            ...parent::getDependencies(),
            LoadGlobalDefaultThemeConfigurationData::class,
        ];
    }

    protected function getFrontendTheme(): ?string
    {
        return 'default';
    }
}

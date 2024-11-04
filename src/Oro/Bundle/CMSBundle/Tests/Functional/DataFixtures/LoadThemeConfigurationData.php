<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Tests\Functional\DataFixtures\LoadThemeConfigurationData as BaseLoadThemeConfigurationData;

class LoadThemeConfigurationData extends BaseLoadThemeConfigurationData implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies(): array
    {
        return [LoadContentBlockData::class];
    }

    #[\Override]
    protected function processConfiguration(array $configuration): array
    {
        $key = ThemeConfiguration::buildOptionKey('header', 'promotional_content');
        $configuration[$key] = $this->getReference('content_block_1')->getId();

        return $configuration;
    }
}

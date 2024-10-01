<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\CMSBundle\Layout\Extension\WidgetsThemeConfigurationExtension;
use Oro\Bundle\CMSBundle\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;

final class WidgetsThemeConfigurationExtensionTest extends TestCase
{
    use TempDirExtension;

    private ThemeConfigurationProvider $themeConfigurationProvider;

    private ConfigurationBuildersProvider $configurationBuildersProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->configurationBuildersProvider = $this->createStub(ConfigurationBuildersProvider::class);
        $this->configurationBuildersProvider
            ->method('getConfigurationTypes')
            ->willReturn(['type']);

        $cacheFile = $this->getTempFile('WidgetsThemeConfigurationExtension');

        $themeConfiguration = new ThemeConfiguration($this->configurationBuildersProvider);
        $themeConfiguration->addExtension(new WidgetsThemeConfigurationExtension());

        $this->themeConfigurationProvider = new ThemeConfigurationProvider(
            $cacheFile,
            false,
            $themeConfiguration,
            '[\w\-]+'
        );
    }

    public function testPrependScreensConfigs(): void
    {
        $bundle1 = new TestBundle1();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1)]);

        $themeDefinition = $this->themeConfigurationProvider->getThemeDefinition('sample_theme');

        $this->assertEquals(
            [
                'layouts' => [
                    'copyright' => [
                        'template1' => 'oro.widget.translatable.label',
                        'template2' => null
                    ],
                ],
            ],
            $themeDefinition['config']['widgets']
        );
    }
}

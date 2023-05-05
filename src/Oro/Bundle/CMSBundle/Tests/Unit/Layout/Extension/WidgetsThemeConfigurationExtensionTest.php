<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\CMSBundle\Layout\Extension\WidgetsThemeConfigurationExtension;
use Oro\Bundle\CMSBundle\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class WidgetsThemeConfigurationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private ThemeConfigurationProvider $themeConfigurationProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('WidgetsThemeConfigurationExtension');

        $themeConfiguration = new ThemeConfiguration();
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

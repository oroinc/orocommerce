<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\Form\Extension\Stub\PageTypeStub;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ThemeBundle\Tests\Functional\DataFixtures\LoadThemeConfigurationData;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class WYSIWYGTypeExtensionTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadThemeConfigurationData::class]);
        $this->updateUserSecurityToken(self::AUTH_USER);
        // Emulate request processing
        $this->emulateRequest();
    }

    public function testFinishView(): void
    {
        $form = self::getContainer()->get('form.factory')
            ->create(PageTypeStub::class, null, ['data_class' => Page::class]);
        $fieldView = $form->get('content')->createView();
        $actualOptions = json_decode(
            $fieldView->vars['attr']['data-page-component-options'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $layoutThemeName = self::getConfigManager(null)->get('oro_frontend.frontend_theme');

        self::assertArrayHasKey('themes', $actualOptions);
        self::assertIsArray($actualOptions['themes']);

        /** @var ThemeManager $themeManager */
        $themeManager = self::getContainer()->get('oro_layout.theme_manager');
        $enabledThemes = $themeManager->getEnabledThemes('commerce');
        $enabledThemeNames = array_keys($enabledThemes);
        foreach ($actualOptions['themes'] as $themeOptions) {
            self::assertContains($themeOptions['name'], $enabledThemeNames);
        }

        $defaultTheme = [
            'name' => 'default',
            'label' => 'Refreshing Teal',
            'stylesheet' => '/build/default/css/styles.css',
            'svgIconsSupport' => true,
        ];
        if ($layoutThemeName === 'default') {
            $defaultTheme['active'] = true;
        }
        $this->assertThemeOptions($defaultTheme, $actualOptions['themes']);
    }

    private function assertThemeOptions(array $themeOptions, array $actualThemesOptions): void
    {
        $hasThemeOptions = false;
        foreach ($actualThemesOptions as $actualThemeOptions) {
            if (($actualThemeOptions['name'] ?? null) === $themeOptions['name']) {
                $hasThemeOptions = true;
                break;
            }
        }
        self::assertTrue($hasThemeOptions, sprintf("Theme's '%s' options are missing", $themeOptions['name']));
        self::assertEquals($themeOptions['label'], $actualThemeOptions['label'] ?? null);
        self::assertMatchesRegularExpression(
            '/^' . preg_quote($themeOptions['stylesheet'], '/') . '(\?|$)/',
            $actualThemeOptions['stylesheet'] ?? ''
        );
        self::assertEquals($themeOptions['active'] ?? null, $actualThemeOptions['active'] ?? null);
        self::assertEquals($themeOptions['svgIconsSupport'], $actualThemeOptions['svgIconsSupport'] ?? null);
    }
}

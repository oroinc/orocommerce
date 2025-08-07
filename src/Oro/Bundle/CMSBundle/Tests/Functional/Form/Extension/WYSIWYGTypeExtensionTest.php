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

    private ?string $initialLayoutThemeName;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadThemeConfigurationData::class]);
        $this->updateUserSecurityToken(self::AUTH_USER);
        // Emulate request processing
        $this->emulateRequest();

        $configManager = self::getConfigManager(null);
        $this->initialLayoutThemeName = $configManager->get('oro_frontend.frontend_theme');
        $configManager->set('oro_frontend.frontend_theme', $this->getActualThemeName());
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager(null);
        $configManager->set('oro_frontend.frontend_theme', $this->initialLayoutThemeName);
        $configManager->flush();
        $configManager->reload();
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
            'stylesheet' => [
                '/build/default/css/critical.css',
                '/build/default/css/styles.css'
            ],
            'svgIconsSupport' => true,
            'active' => $layoutThemeName === 'default',
        ];
        $this->assertThemeOptions($defaultTheme, $actualOptions['themes']);
    }

    private function assertThemeOptions(array $themeOptions, array $actualThemesOptions): void
    {
        $hasThemeOptions = false;
        foreach ($actualThemesOptions as $actualThemeOptions) {
            if (($actualThemeOptions['name'] ?? null) === $themeOptions['name']) {
                $hasThemeOptions = true;

                foreach ($themeOptions['stylesheet'] as $expectedStyle) {
                    $matched = false;
                    foreach ($actualThemeOptions['stylesheet'] as $actualStyle) {
                        if (preg_match('/^' . preg_quote($expectedStyle, '/') . '(\?|$)/', $actualStyle)) {
                            $matched = true;
                            break;
                        }
                    }
                    self::assertTrue(
                        $matched,
                        sprintf(
                            "Stylesheet '%s' not found in actual stylesheets: %s",
                            $expectedStyle,
                            json_encode($actualThemeOptions['stylesheet'])
                        )
                    );
                }

                self::assertEquals($themeOptions['label'], $actualThemeOptions['label'] ?? null);
                self::assertEquals($themeOptions['active'] ?? null, $actualThemeOptions['active'] ?? null);
                self::assertEquals($themeOptions['svgIconsSupport'], $actualThemeOptions['svgIconsSupport'] ?? null);

                break;
            }
        }
        self::assertTrue($hasThemeOptions, sprintf("Theme's '%s' options are missing", $themeOptions['name']));
    }

    private function getActualThemeName(): ?string
    {
        $defaultWebsite = self::getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $themeName = self::getContainer()->get('oro_theme.provider.theme_configuration')->getThemeName($defaultWebsite);
        if ($themeName) {
            return $themeName;
        }

        return self::getConfigManager()->get('oro_frontend.frontend_theme', false, false, $defaultWebsite);
    }
}

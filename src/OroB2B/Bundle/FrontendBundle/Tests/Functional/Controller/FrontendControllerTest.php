<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class FrontendControllerTest extends WebTestCase
{
    const FRONTEND_THEME_CONFIG_KEY = 'oro_b2b_frontend.frontend_theme';
    const DEFAULT_THEME = '';

    protected function setUp()
    {
        $this->initClient();
        $this->setTheme(self::DEFAULT_THEME);
    }

    protected function tearDown()
    {
        $this->setTheme(self::DEFAULT_THEME);
    }

    public function testRedirectToProduct()
    {
        $this->markTestSkipped();
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
                ['HTTP_X-CSRF-Header' => 1]
            )
        );

        $this->client->request('GET', $this->getUrl('orob2b_frontend_root'));
        $crawler = $this->client->followRedirect();
        $this->assertNotContains($this->getBackendPrefix(), $crawler->html());
        $this->assertEquals('Products', $crawler->filter('h1.oro-subtitle')->html());
    }

    public function testThemeSwitch()
    {
        // Switch to layout theme
        $configManager = $this->getContainer()->get('oro_config.manager');
        $this->assertEmpty($configManager->get(self::FRONTEND_THEME_CONFIG_KEY));
        $layoutTheme = 'default';
        $this->setTheme($layoutTheme);

        $this->client->request('GET', $this->getUrl('orob2b_frontend_root'));
        $crawler = $this->client->followRedirect();
        $this->assertEquals('Login', $crawler->filter('title')->html());

        // Check that backend theme was not affected
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_security_login'),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );
        $this->assertEquals('Login', $crawler->filter('h2.title')->html());

        // Check that after selecting of layout there is an ability to switch to oro theme
        $this->setTheme(self::DEFAULT_THEME);

        $this->client->request('GET', $this->getUrl('orob2b_frontend_root'));
        $crawler = $this->client->followRedirect();
        $this->assertEquals(
            'Sign In',
            $crawler->filter('form.create-account__form_signin h2.create-account__title')->html()
        );
    }

    /**
     * @return string
     */
    protected function getBackendPrefix()
    {
        return $this->getContainer()->getParameter('web_backend_prefix');
    }

    /**
     * @param string $theme
     */
    protected function setTheme($theme)
    {
        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set(self::FRONTEND_THEME_CONFIG_KEY, $theme);
        $configManager->flush();
    }
}

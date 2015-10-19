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

    public function testRedirectToLogin()
    {
        $this->initClient();
        $this->client->request('GET', $this->getUrl('_frontend'));
        $crawler = $this->client->followRedirect();
        $this->assertNotContains($this->getBackendPrefix(), $crawler->html());
        $this->assertEquals('Login', $crawler->filter('h2.title')->html());
    }

    public function testRedirectToProduct()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
                ['HTTP_X-CSRF-Header' => 1]
            )
        );

        $this->client->request('GET', $this->getUrl('_frontend'));
        $crawler = $this->client->followRedirect();
        $this->assertNotContains($this->getBackendPrefix(), $crawler->html());
        $this->assertEquals('Products', $crawler->filter('h1.oro-subtitle')->html());
    }

    /**
     * @depends testRedirectToLogin
     */
    public function testThemeSwitch()
    {
        $this->initClient([], [], true);
        
        // Switch to layout theme
        $configManager = $this->getContainer()->get('oro_config.manager');
        $this->assertEmpty($configManager->get(self::FRONTEND_THEME_CONFIG_KEY));
        $configManager->set(self::FRONTEND_THEME_CONFIG_KEY, 'default');
        $configManager->flush();

        $this->client->request('GET', $this->getUrl('_frontend'));
        $crawler = $this->client->followRedirect();
        $this->assertEquals('Hello World!', $crawler->filter('title')->html());

        // Check that after selecting of layout there is an ability to switch to oro theme
        $configManager->set(self::FRONTEND_THEME_CONFIG_KEY, '');
        $configManager->flush();

        $this->client->request('GET', $this->getUrl('_frontend'));
        $crawler = $this->client->followRedirect();
        $this->assertEquals('Login', $crawler->filter('h2.title')->html());
    }

    /**
     * @return string
     */
    protected function getBackendPrefix()
    {
        return $this->getContainer()->getParameter('web_backend_prefix');
    }
}

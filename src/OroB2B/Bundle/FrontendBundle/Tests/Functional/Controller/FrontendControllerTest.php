<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;

class FrontendControllerTest extends WebTestCase
{
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
     * @return string
     */
    protected function getBackendPrefix()
    {
        return $this->getContainer()->getParameter('backend_prefix');
    }
}

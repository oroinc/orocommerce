<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Functional\Controller;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ExceptionControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    /**
     * @dataProvider showActionNotFoundDataProvider
     * @param string $url
     * @param string $selector
     */
    public function testShowActionNotFound($url, $selector)
    {
        $crawler = $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
        $this->assertContains('404 Not Found', $crawler->filter($selector)->text());
    }

    /**
     * @return array
     */
    public function showActionNotFoundDataProvider()
    {
        return [
            [
                'url' => '/page-does-not-exist',
                'selector' => 'div.text-center h1',
            ],
            [
                'url' => '/admin/page-does-not-exist',
                'selector' => 'div.popup-frame div.popup-holder div.pagination-centered.popup-box-errors h1',
            ],
        ];
    }
}

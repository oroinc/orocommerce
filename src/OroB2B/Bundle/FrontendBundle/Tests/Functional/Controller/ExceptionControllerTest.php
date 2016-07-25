<?php

namespace OroB2B\Bundle\FrontendBundle\Tests\Functional\Controller;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ResponseExtension;

/**
 * @dbIsolation
 */
class ExceptionControllerTest extends WebTestCase
{
    use ResponseExtension;

    public function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
            true
        );
    }

    /**
     * @dataProvider showActionNotFoundDataProvider
     *
     * @param string $url
     */
    public function testShowActionNotFound($url)
    {
        $this->client->followRedirects();
        $this->client->request('GET', $url);

        $this->assertLastResponseStatus(404);
        $this->assertLastResponseContentTypeHtml();
    }

    /**
     * @return array
     */
    public function showActionNotFoundDataProvider()
    {
        return [
            'frontend' => [
                'url' => '/page-does-not-exist',
            ],
            'admin' => [
                'url' => '/admin/page-does-not-exist',
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\FrontendBundle\Tests\Functional\Controller;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ResponseExtension;

/**
 * @dbIsolation
 */
class ExceptionControllerTest extends WebTestCase
{
    use ResponseExtension;

    public function testShowActionNotFoundFrontend()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
            true
        );

        $this->client->followRedirects();
        $this->client->request('GET', '/page-does-not-exist');

        $this->assertLastResponseStatus(404);
        $this->assertLastResponseContentTypeHtml();
    }

    public function testShowActionNotFoundBackend()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->client->followRedirects();
        $this->client->request('GET', '/admin/page-does-not-exist');

        $this->assertLastResponseStatus(404);
        $this->assertLastResponseContentTypeHtml();
    }
}

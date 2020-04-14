<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Routing;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadRedirects;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FrontendRedirectTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                LoadRedirects::class
            ]
        );
    }

    public function testRedirect()
    {
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_1);

        $this->client->request(
            'GET',
            $redirect->getFrom()
        );
        $response = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($response, 301);
        $this->assertEquals($redirect->getTo(), $response->headers->get('location'));
    }
}

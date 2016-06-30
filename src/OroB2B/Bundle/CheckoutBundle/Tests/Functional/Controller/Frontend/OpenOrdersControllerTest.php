<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class OpenOrdersControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testOpenOrders()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_checkout_frontend_open_orders'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Open Orders', $crawler->filter('h1.page-title')->html());
    }
}

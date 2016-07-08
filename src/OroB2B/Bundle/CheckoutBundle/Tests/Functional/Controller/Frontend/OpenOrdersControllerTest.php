<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as OroLoadAccountUserData;

/**
 * @dbIsolation
 */
class OpenOrdersControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(OroLoadAccountUserData::AUTH_USER, OroLoadAccountUserData::AUTH_PW)
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

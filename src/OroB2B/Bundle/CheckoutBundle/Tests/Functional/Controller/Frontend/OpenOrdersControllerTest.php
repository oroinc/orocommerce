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
        $this->assertContains('grid-frontend-checkouts-grid', $crawler->html());
    }

    public function testOpenOrdersIfSeparatePageSettingIsTrue()
    {
        $configManager = $this
            ->getContainer()
            ->get('oro_config.manager');

        $configManager->set('oro_b2b_checkout.frontend_open_orders_separate_page', true);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertNotContains('Open Orders', $crawler->filter('h1.page-title')->html());

        $this->assertNotContains('grid-frontend-checkouts-grid', $crawler->html());

        $navigationList = $crawler->filter('ul.account-navigation-list');

        $this->assertContains('Open Orders', $navigationList->html());
    }

    public function testOpenOrdersIfSeparatePageSettingIsFalse()
    {
        $configManager = $this
            ->getContainer()
            ->get('oro_config.manager');

        $configManager->set('oro_b2b_checkout.frontend_open_orders_separate_page', false);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Open Orders', $crawler->filter('h1.page-title')->html());

        $this->assertContains('grid-frontend-checkouts-grid', $crawler->html());

        $navigationList = $crawler->filter('ul.account-navigation-list');

        $this->assertNotContains('Open Orders', $navigationList->html());
    }
}

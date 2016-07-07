<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Functional\OrdersHistory;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class OpenOrdersSeparatePageSettingTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
    }

    public function testShouldNotShowOpenOrdersInOrderHistoryGridIfSeparatePageSettingIsTrue()
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
    }

    public function testShouldShowOpenOrdersInOrderHistoryGridIfSeparatePageSettingIsFalse()
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
    }

    public function testShouldShowOpenOrdersLinkInAccountMenuIfSeparatePageSettingIsTrue()
    {
        $configManager = $this
            ->getContainer()
            ->get('oro_config.manager');

        $configManager->set('oro_b2b_checkout.frontend_open_orders_separate_page', true);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $links = $crawler->filter('ul.account-navigation-list > li > a');
        $found = false;

        foreach ($links as $link) {
            if ($link->nodeValue == 'Open Orders') {
                $found = true;

                break;
            }
        }

        $this->assertTrue($found);
    }

    public function testShouldNotShowOpenOrdersLinkInAccountMenuIfSeparatePageSettingIsFalse()
    {
        $configManager = $this
            ->getContainer()
            ->get('oro_config.manager');

        $configManager->set('oro_b2b_checkout.frontend_open_orders_separate_page', false);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $links = $crawler->filter('ul.account-navigation-list > li > a');
        $found = false;

        foreach ($links as $link) {
            if ($link->nodeValue == 'Open Orders') {
                $found = true;

                break;
            }
        }

        $this->assertFalse($found);
    }
}

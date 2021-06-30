<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as OroLoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OpenOrdersControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(OroLoadCustomerUserData::AUTH_USER, OroLoadCustomerUserData::AUTH_PW)
        );
    }

    public function testOpenOrdersWhenConfigIsOff()
    {
        self::getConfigManager('global')->set('oro_checkout.frontend_show_open_orders', false);

        $this->client->request('GET', $this->getUrl('oro_checkout_frontend_open_orders'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 404);

        self::getConfigManager('global')->set('oro_checkout.frontend_show_open_orders', true);
    }

    public function testOpenOrders()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_checkout_frontend_open_orders'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('Open Orders', $crawler->filter('h1.page-title')->html());
        static::assertStringContainsString('grid-frontend-checkouts-grid', $crawler->html());
    }

    public function testOpenOrdersIfSeparatePageSettingIsTrue()
    {
        $configManager = self::getConfigManager('global');

        $configManager->set('oro_checkout.frontend_open_orders_separate_page', true);
        $configManager->flush();
        // Clears cache in general config manager.
        self::getConfigManager(null)->reload();

        $crawler = $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringNotContainsString('grid-frontend-checkouts-grid', $crawler->html());

        $navigationList = $crawler->filter('ul.primary-menu');

        static::assertStringContainsString('Open Orders', $navigationList->html());
    }

    public function testOpenOrdersIfSeparatePageSettingIsFalse()
    {
        $configManager = self::getConfigManager('global');

        $configManager->set('oro_checkout.frontend_open_orders_separate_page', false);
        $configManager->flush();
        // Clears cache in general config manager.
        self::getConfigManager(null)->reload();

        $crawler = $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString('grid-frontend-checkouts-grid', $crawler->html());

        $navigationList = $crawler->filter('ul.primary-menu');

        static::assertStringNotContainsString('Open Orders', $navigationList->html());
    }
}

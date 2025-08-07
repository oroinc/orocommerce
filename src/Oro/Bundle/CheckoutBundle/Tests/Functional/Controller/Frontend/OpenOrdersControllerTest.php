<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as OroLoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OpenOrdersControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(OroLoadCustomerUserData::AUTH_USER, OroLoadCustomerUserData::AUTH_PW)
        );
    }

    public function testOpenOrdersWhenConfigIsOff(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.frontend_show_open_orders', false);
        $configManager->flush();
        try {
            $this->client->request('GET', $this->getUrl('oro_checkout_frontend_open_orders'));
            $result = $this->client->getResponse();
        } finally {
            $configManager->set('oro_checkout.frontend_show_open_orders', true);
            $configManager->flush();
        }

        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testOpenOrders(): void
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_checkout_frontend_open_orders'));
        $result = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Open Orders', $crawler->filter('h1.page-title')->html());
        self::assertStringContainsString('grid-frontend-checkouts-grid', $crawler->html());
    }

    public function testOpenOrdersIfSeparatePageSettingIsTrue(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.frontend_open_orders_separate_page', true);
        $configManager->flush();
        try {
            $crawler = $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
            $result = $this->client->getResponse();
        } finally {
            $configManager->set('oro_checkout.frontend_open_orders_separate_page', false);
            $configManager->flush();
        }

        self::assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringNotContainsString('grid-frontend-checkouts-grid', $crawler->html());

        $navigationList = $crawler->filter('ul.primary-menu');

        self::assertStringContainsString('Open Orders', $navigationList->html());
    }

    public function testOpenOrdersIfSeparatePageSettingIsFalse(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_checkout.frontend_open_orders_separate_page', false);
        $configManager->flush();
        try {
            $crawler = $this->client->request('GET', $this->getUrl('oro_order_frontend_index'));
            $result = $this->client->getResponse();
        } finally {
            $configManager->set('oro_checkout.frontend_open_orders_separate_page', true);
            $configManager->flush();
        }

        self::assertHtmlResponseStatusCodeEquals($result, 200);

        self::assertStringContainsString('grid-frontend-checkouts-grid', $crawler->html());

        $navigationList = $crawler->filter('ul.primary-menu');

        self::assertStringNotContainsString('Open Orders', $navigationList->html());
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class QuickAddFeatureToggleTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?bool $initialEnableQuickOrderForm;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->initialEnableQuickOrderForm = self::getConfigManager()->get('oro_product.enable_quick_order_form');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        if ($configManager->get('oro_product.enable_quick_order_form') !== $this->initialEnableQuickOrderForm) {
            $configManager->set('oro_product.enable_quick_order_form', $this->initialEnableQuickOrderForm);
            $configManager->flush();
        }

        parent::tearDown();
    }

    /**
     * @dataProvider actionsProvider
     */
    public function testActions(string $route)
    {
        $this->client->request('GET', $this->getUrl($route));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $configManager = self::getConfigManager();
        $configManager->set('oro_product.enable_quick_order_form', false);
        $configManager->flush();

        $this->client->request('GET', $this->getUrl($route));
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    public function actionsProvider(): array
    {
        return [
            ['oro_product_frontend_quick_add'],
            ['oro_product_frontend_quick_add_import'],
            ['oro_product_frontend_quick_add_copy_paste'],
            ['oro_product_frontend_quick_add_import_help'],
        ];
    }

    public function testIndexPageWithQuickOrder()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        $linksCrawler = $crawler->selectLink('Quick Order');
        $this->assertEquals(1, $linksCrawler->count());
        $this->assertEquals($this->getUrl('oro_product_frontend_quick_add', [], true), $linksCrawler->link()->getUri());
    }

    public function testIndexPageWithoutQuickOrder()
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_product.enable_quick_order_form', false);
        $configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        $linksCrawler = $crawler->selectLink('Quick Order');
        $this->assertEquals(0, $linksCrawler->count());
    }
}

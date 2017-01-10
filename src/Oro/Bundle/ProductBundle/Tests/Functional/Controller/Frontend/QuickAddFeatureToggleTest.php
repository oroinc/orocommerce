<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class QuickAddFeatureToggleTest extends WebTestCase
{
    /** @var ConfigManager $configManager */
    protected $configManager;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->configManager = $this->getContainer()->get('oro_config.manager');
    }

    protected function tearDown()
    {
        $this->configManager->reset('oro_product.enable_quick_order_form');
        $this->configManager->flush();
    }

    /**
     * @dataProvider actionsProvider
     * @param string  $route
     */
    public function testActions($route)
    {
        $this->client->request('GET', $this->getUrl($route));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->configManager->set('oro_product.enable_quick_order_form', false);
        $this->configManager->flush();

        $this->client->request('GET', $this->getUrl($route));
        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    /**
     * @return array
     */
    public function actionsProvider()
    {
        return [
            ['oro_product_frontend_quick_add'],
            ['oro_product_frontend_quick_add_import'],
            ['oro_product_frontend_quick_add_copy_paste'],
            ['oro_product_frontend_quick_add_validation_result'],
        ];
    }

    public function testIndexPageWithQuickOrder()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $linksCrawler = $crawler->selectLink('Quick Order Form');
        $this->assertEquals(1, $linksCrawler->count());
        $this->assertEquals($this->getUrl('oro_product_frontend_quick_add', [], true), $linksCrawler->link()->getUri());
    }

    public function testIndexPageWithoutQuickOrder()
    {
        $this->configManager->set('oro_product.enable_quick_order_form', false);
        $this->configManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $linksCrawler = $crawler->selectLink('Quick Order Form');
        $this->assertEquals(0, $linksCrawler->count());
    }
}

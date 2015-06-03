<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @dbIsolation
 */
class AjaxProductPriceControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(),
                [
                    'HTTP_X-CSRF-Header' => 1,
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            )
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );
    }

    public function testCreate()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_product_price_create_widget',
                [
                    'priceListId' => $priceList->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'orob2b_pricing_price_list_product_price[product]' => $product->getId(),
                'orob2b_pricing_price_list_product_price[quantity]' => 10,
                'orob2b_pricing_price_list_product_price[unit]' => $unit->getCode(),
                'orob2b_pricing_price_list_product_price[price][value]' => 20,
                'orob2b_pricing_price_list_product_price[price][currency]' => 'USD'
            ]
        );

        $this->assertSaved($form);
    }

    public function testUpdate()
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_product_price_update_widget',
                [
                    'id' => $productPrice->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'orob2b_pricing_price_list_product_price[quantity]' => 10,
                'orob2b_pricing_price_list_product_price[unit]' => $unit->getCode(),
                'orob2b_pricing_price_list_product_price[price][value]' => 20,
                'orob2b_pricing_price_list_product_price[price][currency]' => 'USD'
            ]
        );

        $this->assertSaved($form);
    }

    /**
     * @param Form $form
     */
    protected function assertSaved(Form $form)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertRegExp('/"savedId":\s*\d+/i', $html);
    }
}

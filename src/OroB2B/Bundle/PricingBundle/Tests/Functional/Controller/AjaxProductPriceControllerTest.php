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
    /** @var string */
    protected $pricesByPriceListActionUrl = 'orob2b_pricing_price_by_account';

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
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );
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

    public function testUpdateDuplicateEntry()
    {
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');
        $productPriceEUR = $this->getReference('product_price.11');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_product_price_update_widget',
                [
                    'id' => $productPriceEUR->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'orob2b_pricing_price_list_product_price[quantity]' => $productPrice->getQuantity(),
                'orob2b_pricing_price_list_product_price[unit]' => $productPrice->getUnit()->getCode(),
                'orob2b_pricing_price_list_product_price[price][value]' => $productPrice->getPrice()->getValue(),
                'orob2b_pricing_price_list_product_price[price][currency]' => $productPrice->getPrice()->getCurrency(),
            ]
        );

        $this->assertSubmitError($form, 'orob2b.pricing.validators.product_price.unique_entity.message');
    }

    protected function assertSubmitError($form, $message)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertRegExp('/"savedId":\s*null/i', $html);
        $error = $this->getContainer()->get('translator')
            ->trans($message, [], 'validators');
        $this->assertContains($error, $html);
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

    /**
     * @dataProvider unitDataProvider
     * @param string $priceList
     * @param string $product
     * @param null|string $currency
     * @param array $expected
     */
    public function testGetProductUnitsByCurrencyAction($priceList, $product, $currency = null, array $expected = [])
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);
        /** @var Product $product */
        $product = $this->getReference($product);

        $params = [
            'id' => $product->getId(),
            'price_list_id' => $priceList->getId(),
            'currency' => $currency
        ];

        $this->client->request('GET', $this->getUrl('orob2b_pricing_units_by_pricelist', $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('units', $data);
        $this->assertEquals($expected, array_keys($data['units']));
    }

    /**
     * @return array
     */
    public function unitDataProvider()
    {
        return [
            [
                'price_list_1',
                'product.1',
                null,
                ['bottle', 'liter']
            ],
            [
                'price_list_1',
                'product.1',
                'EUR',
                ['bottle']
            ]
        ];
    }
}

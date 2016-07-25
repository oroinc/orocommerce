<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    /**
     * @var array
     */
    protected $newPrice = [
        'quantity' => 1,
        'unit'     => 'box',
        'price'    => '12.34',
        'currency' => 'EUR',
    ];

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices']);
    }

    public function testSidebar()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_product_sidebar'),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var PriceListRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class')
        );
        /** @var PriceList $defaultPriceList */
        $defaultPriceList = $repository->getDefault();

        $this->assertEquals(
            $defaultPriceList->getId(),
            $crawler->filter('.sidebar-item.default-price-list-choice input[type=hidden]')->attr('value')
        );

        foreach ($crawler->filter('.sidebar-item.currencies input[type=checkbox]')->children() as $checkbox) {
            $this->assertContains($checkbox->attr('value'), $defaultPriceList->getCurrencies());
        }

        $this->assertContains(
            $this->getContainer()->get('translator')->trans('orob2b.pricing.productprice.show_tier_prices.label'),
            $crawler->filter('.sidebar-item.show-tier-prices-choice')->html()
        );
    }

    public function testPriceListFromRequest()
    {
        /** @var PriceListRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class')
        );
        /** @var PriceList $priceList */
        $priceList = $repository->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(
            $priceList->getId(),
            $crawler->filter('.sidebar-item.default-price-list-choice input[type=hidden]')->attr('value')
        );
    }

    public function testPriceListCurrenciesFromRequestUnchecked()
    {
        /** @var PriceListRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class')
        );
        /** @var PriceList $priceList */
        $priceList = $repository->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                    PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => false,
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-item.currencies input[type=checkbox]')
        );
        $this->assertCount(0, $crawler->filter('.sidebar-item.currencies input[type=checkbox][checked=checked]'));
        $crawler->filter('.sidebar-item.currencies input[type=checkbox]')->each(
            function (Crawler $node) use ($priceList) {
                $this->assertContains($node->attr('value'), $priceList->getCurrencies());
                $this->assertEmpty($node->attr('checked'));
            }
        );
    }

    public function testPriceListCurrenciesFromRequestChecked()
    {
        /** @var PriceListRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class')
        );
        /** @var PriceList $priceList */
        $priceList = $repository->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                    PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => $priceList->getCurrencies(),
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-item.currencies input[type=checkbox]')
        );
        $this->assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-item.currencies input[type=checkbox][checked=checked]')
        );
        $crawler->filter('.sidebar-item.currencies input[type=checkbox]')->each(
            function (Crawler $node) use ($priceList) {
                $this->assertContains($node->attr('value'), $priceList->getCurrencies());
                $this->assertNotEmpty($node->attr('checked'));
            }
        );
    }

    public function testPriceListCurrenciesFromRequestPartialChecked()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $selectedCurrencies = array_diff($priceList->getCurrencies(), ['EUR']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                    PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => $selectedCurrencies,
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-item.currencies input[type=checkbox]')
        );
        $this->assertSameSize(
            $selectedCurrencies,
            $crawler->filter('.sidebar-item.currencies input[type=checkbox][checked=checked]')
        );
        $crawler->filter('.sidebar-item.currencies input[type=checkbox]')->each(
            function (Crawler $node) use ($priceList, $selectedCurrencies) {
                $this->assertContains($node->attr('value'), $priceList->getCurrencies());

                if (in_array($node->attr('value'), $selectedCurrencies, true)) {
                    $this->assertNotEmpty($node->attr('checked'));
                } else {
                    $this->assertEmpty($node->attr('checked'));
                }
            }
        );
    }

    public function testShowTierPricesChecked()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::TIER_PRICES_KEY => true,
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(
            'checked',
            $crawler->filter('.sidebar-item.show-tier-prices-choice input[type=checkbox]')->attr('checked')
        );
    }

    public function testShowTierPricesNotChecked()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::TIER_PRICES_KEY => false,
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(
            '',
            $crawler->filter('.sidebar-item.show-tier-prices-choice input[type=checkbox]')->attr('checked')
        );
    }

    public function testNewPriceWithNewUnit()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_update', ['id' => $product->getId()])
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['orob2b_product']['additionalUnitPrecisions'][] = [
            'unit' => $this->newPrice['unit'],
            'precision' => 0
        ];
        $formValues['orob2b_product']['prices'][] = [
            'priceList' => $priceList->getId(),
            'quantity' => $this->newPrice['quantity'],
            'unit' => $this->newPrice['unit'],
            'price' => [
                'value' => $this->newPrice['price'],
                'currency' => $this->newPrice['currency'],
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Product has been saved', $crawler->html());

        /** @var ProductPrice $price */
        $price = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:ProductPrice')
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->findOneBy([
                'product' => $product,
                'priceList' => $priceList,
                'quantity' => $this->newPrice['quantity'],
                'unit' => $this->newPrice['unit'],
                'currency' => $this->newPrice['currency'],
            ]);
        $this->assertNotEmpty($price);
        $this->assertEquals($this->newPrice['price'], $price->getPrice()->getValue());
    }

    /**
     * @dataProvider filterDataProvider
     * @param array $filter
     * @param string $priceListReference
     * @param array $expected
     */
    public function testGridFilter(array $filter, $priceListReference, array $expected)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListReference);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'products-grid',
                PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
            ],
            $filter,
            true
        );
        $result = $this->getJsonResponseContent($response, 200);

        $this->assertArrayHasKey('data', $result);
        $this->assertSameSize($expected, $result['data']);

        foreach ($result['data'] as $product) {
            $this->assertContains($product['sku'], $expected);
        }
    }

    /**
     * @return array
     */
    public function filterDataProvider()
    {
        return [
            'equal 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => null,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product.1']
            ],
            'greater equal 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => NumberFilterType::TYPE_GREATER_EQUAL,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product.1', 'product.2']
            ],
            'less 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => NumberFilterType::TYPE_LESS_THAN,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product.3']
            ],
            'greater 10 USD per liter AND less 20 EUR per bottle' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => NumberFilterType::TYPE_GREATER_EQUAL,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter',
                    'products-grid[_filter][price_column_eur][value]' => 20,
                    'products-grid[_filter][price_column_eur][type]'  => NumberFilterType::TYPE_LESS_THAN,
                    'products-grid[_filter][price_column_eur][unit]'  => 'bottle'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product.1']
            ],
        ];
    }
}

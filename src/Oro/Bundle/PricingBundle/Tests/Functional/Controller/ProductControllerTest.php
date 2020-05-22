<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\Controller\ProductHelperTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductControllerTest extends ProductHelperTestCase
{
    /**
     * @var array
     */
    protected $newPrice = [
        'quantity' => 1,
        'unit' => 'box',
        'price' => '12.3400',
        'currency' => 'EUR',
    ];

    protected function setUp(): void
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures(['Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices']);
    }

    public function testSidebar()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_pricing_price_product_sidebar'),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var PriceListRepository $repository */
        $repository = static::getContainer()->get('doctrine')->getRepository(PriceList::class);
        /** @var PriceList $defaultPriceList */
        $defaultPriceList = $repository->getDefault();

        static::assertEquals(
            $defaultPriceList->getId(),
            $crawler->filter('.sidebar-items .default-price-list-choice input[type=hidden]')->attr('value')
        );

        foreach ($crawler->filter('.sidebar-items .currencies input[type=checkbox]')->children() as $checkbox) {
            static::assertContains($checkbox->attr('value'), $defaultPriceList->getCurrencies());
        }

        static::assertStringContainsString(
            static::getContainer()->get('translator')->trans('oro.pricing.productprice.show_tier_prices.label'),
            $crawler->filter('.sidebar-items .show-tier-prices-choice')->html()
        );
    }

    public function testPriceListFromRequest()
    {
        /** @var PriceListRepository $repository */
        $repository = static::getContainer()->get('doctrine')->getRepository(PriceList::class);
        /** @var PriceList $priceList */
        $priceList = $repository->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertEquals(
            $priceList->getId(),
            $crawler->filter('.sidebar-items .default-price-list-choice input[type=hidden]')->attr('value')
        );
    }

    public function testPriceListCurrenciesFromRequestUnchecked()
    {
        /** @var PriceListRepository $repository */
        $repository = static::getContainer()->get('doctrine')->getRepository(PriceList::class);
        /** @var PriceList $priceList */
        $priceList = $repository->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                    PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => false,
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-items .currencies input[type=checkbox]')
        );
        static::assertCount(0, $crawler->filter('.sidebar-items .currencies input[type=checkbox][checked=checked]'));
        $crawler->filter('.sidebar-items .currencies input[type=checkbox]')->each(
            function (Crawler $node) use ($priceList) {
                static::assertContains($node->attr('value'), $priceList->getCurrencies());
                static::assertEmpty($node->attr('checked'));
            }
        );
    }

    public function testPriceListCurrenciesFromRequestChecked()
    {
        /** @var PriceListRepository $repository */
        $repository = static::getContainer()->get('doctrine')->getRepository(PriceList::class);
        /** @var PriceList $priceList */
        $priceList = $repository->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                    PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => $priceList->getCurrencies(),
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-items .currencies input[type=checkbox]')
        );
        static::assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-items .currencies input[type=checkbox][checked=checked]')
        );
        $crawler->filter('.sidebar-items .currencies input[type=checkbox]')->each(
            function (Crawler $node) use ($priceList) {
                static::assertContains($node->attr('value'), $priceList->getCurrencies());
                static::assertNotEmpty($node->attr('checked'));
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
                'oro_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                    PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => $selectedCurrencies,
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-items .currencies input[type=checkbox]')
        );
        static::assertSameSize(
            $selectedCurrencies,
            $crawler->filter('.sidebar-items .currencies input[type=checkbox][checked=checked]')
        );
        $crawler->filter('.sidebar-items .currencies input[type=checkbox]')->each(
            function (Crawler $node) use ($priceList, $selectedCurrencies) {
                static::assertContains($node->attr('value'), $priceList->getCurrencies());

                if (in_array($node->attr('value'), $selectedCurrencies, true)) {
                    static::assertNotEmpty($node->attr('checked'));
                } else {
                    static::assertEmpty($node->attr('checked'));
                }
            }
        );
    }

    public function testShowTierPricesChecked()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::TIER_PRICES_KEY => true,
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertEquals(
            'checked',
            $crawler->filter('.sidebar-items .show-tier-prices-choice input[type=checkbox]')->attr('checked')
        );
    }

    public function testShowTierPricesNotChecked()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::TIER_PRICES_KEY => false,
                ]
            ),
            ['_widgetContainer' => 'widget']
        );
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertEquals(
            '',
            $crawler->filter('.sidebar-items .show-tier-prices-choice input[type=checkbox]')->attr('checked')
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
            $this->getUrl('oro_product_update', ['id' => $product->getId()])
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['oro_product']['additionalUnitPrecisions'][] = [
            'unit' => $this->newPrice['unit'],
            'precision' => 0
        ];
        $formValues['oro_product']['prices'][] = [
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
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('Product has been saved', $crawler->html());

        /** @var ProductPrice $price */
        $shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        $prices = static::getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:ProductPrice')
            ->getRepository('OroPricingBundle:ProductPrice')
            ->findByPriceList($shardManager, $priceList, [
                'product' => $product,
                'priceList' => $priceList,
                'quantity' => $this->newPrice['quantity'],
                'unit' => $this->newPrice['unit'],
                'currency' => $this->newPrice['currency'],
            ]);

        static::assertNotEmpty($prices);
        $price = $prices[0];
        static::assertEquals($this->newPrice['price'], $price->getPrice()->getValue());
    }

    public function testPricesUnitsSwap()
    {
        $this->markTestIncomplete('Randomly failing test. TODO: BB-11393');

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_update', ['id' => $product->getId()])
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();

        $formValues['oro_product']['prices'][0]['unit'] = 'box';
        $formValues['oro_product']['prices'][2]['unit'] = 'bottle';

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $result = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('Product has been saved', $crawler->html());

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_update', ['id' => $product->getId()])
        );

        $actualValues = $crawler
            ->selectButton('Save and Close')
            ->form()
            ->getPhpValues();

        $box = $formValues['oro_product']['prices'][0];
        $bottle = $formValues['oro_product']['prices'][2];

        $formValues['oro_product']['prices'][0] = $bottle;
        $formValues['oro_product']['prices'][2] = $box;

        static::assertEquals(
            $formValues,
            $actualValues
        );
    }

    /**
     * @dataProvider filterDataProvider
     *
     * @param array  $filter
     * @param string $priceListReference
     * @param array  $expected
     */
    public function testGridFilter(array $filter, $priceListReference, array $expected)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListReference);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'products-grid',
                PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => ['USD', 'EUR'],
            ],
            $filter,
            true
        );
        $result = static::getJsonResponseContent($response, 200);

        static::assertArrayHasKey('data', $result);
        static::assertSameSize($expected, $result['data']);

        foreach ($result['data'] as $product) {
            static::assertContains($product['sku'], $expected);
        }
    }

    public function testDuplicate()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $product->getId()]));
        $this->client->followRedirects(true);

        $crawler = $this->client->getCrawler();
        $button = $crawler->selectLink('Duplicate');
        $this->assertCount(1, $button);

        $this->assertExecuteOperation(
            'oro_product_duplicate',
            $product->getId(),
            Product::class
        );
        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('redirectUrl', $data);

        $this->client->request('GET', $data['redirectUrl']);
        $newProduct = $this->getProductDataBySku($product->getSku().'-1');

        $shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        /** @var ProductPriceRepository $productPriceRepository */
        $productPriceRepository = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(ProductPrice::class);

        $newPrices = $productPriceRepository->getPricesByProduct($shardManager, $newProduct);
        $oldPrices = $productPriceRepository->getPricesByProduct($shardManager, $product);

        $this->assertNotEmpty($newPrices);
        $this->assertCount(count($oldPrices), $newPrices);
        foreach ($newPrices as $key => $price) {
            $price->loadPrice();
            $oldPrices[$key]->loadPrice();
            $expected = [
                'priceList' => $oldPrices[$key]->getPriceList()->getName(),
                'quantity' => $oldPrices[$key]->getQuantity(),
                'unit' => $oldPrices[$key]->getUnit(),
                'price' => $oldPrices[$key]->getPrice(),
            ];

            $this->assertEquals($expected, [
                'priceList' => $price->getPriceList()->getName(),
                'quantity' => $price->getQuantity(),
                'unit' => $price->getUnit(),
                'price' => $price->getPrice(),
            ]);
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
                    'products-grid[_filter][price_column_usd][type]' => null,
                    'products-grid[_filter][price_column_usd][unit]' => 'liter'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product-1']
            ],
            'greater equal 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]' => NumberFilterType::TYPE_GREATER_EQUAL,
                    'products-grid[_filter][price_column_usd][unit]' => 'liter'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product-1', 'product-2']
            ],
            'less 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]' => NumberFilterType::TYPE_LESS_THAN,
                    'products-grid[_filter][price_column_usd][unit]' => 'liter'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product-3']
            ],
            'greater 10 USD per liter AND less 20 EUR per bottle' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]' => NumberFilterType::TYPE_GREATER_EQUAL,
                    'products-grid[_filter][price_column_usd][unit]' => 'liter',
                    'products-grid[_filter][price_column_eur][value]' => 20,
                    'products-grid[_filter][price_column_eur][type]' => NumberFilterType::TYPE_LESS_THAN,
                    'products-grid[_filter][price_column_eur][unit]' => 'bottle'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product-1']
            ],
        ];
    }

    public function testIndexPageHasImportExportAttributePricesButtons()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_index'));
        $result = $this->client->getResponse();

        static::assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString('Export Price Attribute Data', $crawler->html());
        static::assertStringContainsString('Import file', $crawler->html());
    }

    /**
     * @param string $operationName
     * @param mixed $entityId
     * @param string $entityClass
     * @param array $data
     * @param array $server
     * @param int $expectedCode
     * @return Crawler
     */
    protected function assertExecuteOperation(
        $operationName,
        $entityId,
        $entityClass,
        array $data = [],
        array $server = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
        $expectedCode = Response::HTTP_OK
    ) {
        $actionContext = [
            'operationName' => $operationName,
            'entityId'      => $entityId,
            'entityClass'   => $entityClass
        ];
        $data = array_merge($actionContext, $data);
        $url = $this->getUrl('oro_action_operation_execute', $data);
        $dataGrid = $data['datagrid'] ?? null;
        $params = $this->getOperationExecuteParams($operationName, $entityId, $entityClass, $dataGrid);
        $crawler = $this->client->request('POST', $url, $params, [], $server);

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), $expectedCode);

        return $crawler;
    }

    /**
     * @param $operationName
     * @param $entityId
     * @param $entityClass
     * @param $datagrid
     *
     * @return array
     */
    protected function getOperationExecuteParams($operationName, $entityId, $entityClass, $datagrid = null)
    {
        $actionContext = [
            'entityId'    => $entityId,
            'entityClass' => $entityClass,
            'datagrid'    => $datagrid
        ];
        $container = self::getContainer();
        $operation = $container->get('oro_action.operation_registry')->findByName($operationName);
        $actionData = $container->get('oro_action.helper.context')->getActionData($actionContext);

        $tokenData = $container
            ->get('oro_action.operation.execution.form_provider')
            ->createTokenData($operation, $actionData);
        $container->get('session')->save();

        return $tokenData;
    }
}

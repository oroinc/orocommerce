<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;
use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\DataStorageAwareProcessor;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    const ORDER_PO_NUMBER = 'PO-NUMBER';
    const QUICK_ADD_ORDER_PO_NUMBER = 'QUICK-ADD-PO-NUMBER';
    const ORDER_PO_NUMBER_UPDATED = 'PO-NUMBER-UP';

    /**
     * @var DateTimeFormatter
     */
    protected $dateFormatter;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
            ]
        );

        $this->dateFormatter = $this->getContainer()->get('oro_locale.formatter.date_time');
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_index'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals('Orders', $crawler->filter('h1.oro-subtitle')->html());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $this->setDefaultPriceList('price_list_1');

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();
        $date = (new \DateTime('now'))->format('Y-m-d');

        /** @var Product $product */
        $product = $this->getReference('product.1');

        $lineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 10,
                'productUnit' => 'liter',
                'shipBy' => $date,
            ],
        ];

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_order_frontend_type' => [
                '_token' => $form['orob2b_order_frontend_type[_token]']->getValue(),
                'poNumber' => self::ORDER_PO_NUMBER,
                'shipUntil' => $date,
                'customerNotes' => 'Customer Notes',
                'lineItems' => $lineItems,
            ],
        ];

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertViewPage($crawler, [
            self::ORDER_PO_NUMBER,
            'Customer Notes',
            $date,
        ]);

        $actualLineItems = $this->getActualLineItems($crawler, count($lineItems));

        /** @var ProductPrice $price */
        $productPrice = $this->getReference('product_price.1');
        $expectedLineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 10,
                'productUnit' => 'orob2b.product_unit.liter.label.full',
                'price' => $productPrice->getPrice()->getValue(),
                'shipBy' => $date,
            ],
        ];

        $this->assertEquals($expectedLineItems, $actualLineItems);
    }

    public function testQuickAdd()
    {
        $this->setDefaultPriceList('price_list_1');

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_frontend_quick_add'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->filter('form[name="orob2b_product_quick_add"]')->form();

        /** @var Product $product */
        $product = $this->getReference('product.3');

        $products = [
            [
                'productSku' => $product->getSku(),
                'productQuantity' => 15
            ]
        ];

        /** @var DataStorageAwareProcessor $processor */
        $processor = $this->getContainer()->get('orob2b_order.processor.quick_add');

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            $form->getMethod(),
            $form->getUri(),
            [
                'orob2b_product_quick_add' => [
                    '_token' => $form['orob2b_product_quick_add[_token]']->getValue(),
                    'products' => $products,
                    'component' => $processor->getName()
                ]
            ]
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $expectedQuickAddLineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 15,
            ]
        ];

        $this->assertEquals($expectedQuickAddLineItems, $this->getActualLineItems($crawler, count($products), true));

        $form = $crawler->selectButton('Save')->form();
        $form['input_action'] = 'save_and_stay';
        $form['orob2b_order_frontend_type[poNumber]'] = self::QUICK_ADD_ORDER_PO_NUMBER;

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertContains('Order has been saved', $crawler->html());

        $this->assertViewPage($crawler, [self::QUICK_ADD_ORDER_PO_NUMBER]);

        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.9');

        $expectedLineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 15,
                'productUnit' => 'orob2b.product_unit.liter.label.full',
                'price' => $productPrice->getPrice()->getValue(),
                'shipBy' => null
            ]
        ];

        $this->assertEquals($expectedLineItems, $this->getActualLineItems($crawler, count($products)));
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testUpdate()
    {
        $id = $this->findInGrid(
            'frontend-orders-grid',
            ['frontend-orders-grid[_filter][poNumber][value]' => self::ORDER_PO_NUMBER]
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_frontend_update', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();

        /** @var PriceList $defaultPriceList */
        $defaultPriceList = $this->getReference('price_list_1');

        $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:PriceList')
            ->getRepository('OroB2BPricingBundle:PriceList')->setDefault($defaultPriceList);

        /** @var Product $product */
        $product = $this->getReference('product.2');

        $date = (new \DateTime('now'))->format('Y-m-d');

        $lineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 15,
                'productUnit' => 'liter',
                'shipBy' => $date,
            ],
        ];

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_order_frontend_type' => [
                '_token' => $form['orob2b_order_frontend_type[_token]']->getValue(),
                'poNumber' => self::ORDER_PO_NUMBER_UPDATED,
                'lineItems' => $lineItems,
            ],
        ];

        $this->client->followRedirects(true);

        // Submit form
        $result = $this->client->getResponse();
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // Check updated order
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_update', ['id' => $id]));

        $this->assertEquals(
            self::ORDER_PO_NUMBER_UPDATED,
            $crawler->filter('input[name="orob2b_order_frontend_type[poNumber]"]')
                ->extract('value')[0]
        );

        /** @var ProductPrice $price */
        $productPrice = $this->getReference('product_price.1');
        $expectedLineItems = [
            [
                'product' => $product->getId(),
                'quantity' => 15,
                'productUnit' => 'orob2b.product_unit.liter.label.full',
                'price' => $productPrice->getPrice()->getValue(),
                'shipBy' => $date,
            ],
        ];

        $actualLineItems = $this->getActualLineItems($crawler, count($lineItems));

        $this->assertEquals($expectedLineItems, $actualLineItems);

        return $id;
    }

    /**
     * @depends testUpdate
     *
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_view', ['id' => $id]));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertViewPage($crawler, ['Notes', self::ORDER_PO_NUMBER_UPDATED]);
    }

    /**
     * @param Crawler $crawler
     * @param array $expectedViewData
     */
    public function assertViewPage(Crawler $crawler, array $expectedViewData)
    {
        $html = $crawler->html();
        foreach ($expectedViewData as $data) {
            $this->assertContains($data, $html);
        }
    }

    /**
     * @param array $orderData
     * @return array
     */
    protected function getFormData(array $orderData)
    {
        $result = [];
        foreach ($orderData as $field => $value) {
            $formFieldName = sprintf('%s[%s]', FrontendOrderType::NAME, $field);
            $result[$formFieldName] = $value;
        }

        return $result;
    }

    /**
     * @param array $filters
     * @return array
     */
    protected function findInGrid($gridName, array $filters)
    {
        $response = $this->requestFrontendGrid($gridName, $filters);

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        return $result['id'];
    }

    /**
     * @param Crawler $crawler
     * @param int $count
     * @param null $quickAdd
     * @return array
     */
    protected function getActualLineItems(Crawler $crawler, $count, $quickAdd = null)
    {
        $result = [];

        for ($i = 0; $i < $count; $i++) {
            $data = [
                'product' => $crawler->filter('input[name="orob2b_order_frontend_type[lineItems]['.$i.'][product]"]')
                    ->extract('value')[0],
                'quantity' => $crawler->filter('input[name="orob2b_order_frontend_type[lineItems]['.$i.'][quantity]"]')
                    ->extract('value')[0]
            ];

            if ($quickAdd) {
                $result[] = $data;
            } else {
                $result[] = array_merge(
                    $data,
                    [
                        'productUnit' => $crawler
                            ->filter(
                                'select[name="orob2b_order_frontend_type[lineItems]['.$i.'][productUnit]"] :selected'
                            )
                            ->html(),
                        'price' => trim(
                            $crawler->filter(
                                'tr[data-content="orob2b_order_frontend_type[lineItems]['
                                .$i.']"] .order-line-item-price-value'
                            )
                                ->html()
                        ),
                        'shipBy' => $crawler->filter(
                            'input[name="orob2b_order_frontend_type[lineItems]['.$i.'][shipBy]"]'
                        )
                            ->extract('value')[0]
                    ]
                );
            }
        }

        return $result;
    }

    /**
     * @param $name
     */
    protected function setDefaultPriceList($name)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($name);
        $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:PriceList')
            ->getRepository('OroB2BPricingBundle:PriceList')->setDefault($priceList);
    }
}

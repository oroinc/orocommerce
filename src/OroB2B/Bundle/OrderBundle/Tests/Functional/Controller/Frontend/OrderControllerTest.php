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

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    const ORDER_PO_NUMBER = 'PO-NUMBER';
    const ORDER_PO_NUMBER_UPDATED = 'PO-NUMBER-UP';

    /**
     * @var DateTimeFormatter
     */
    protected $dateFormatter;

    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW),
                ['HTTP_X-CSRF-Header' => 1]
            )
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
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_order_frontend_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $date = new \DateTime();
        $this->assertOrderSave(
            $crawler,
            [
                'poNumber' => self::ORDER_PO_NUMBER,
                'shipUntil' => $date->format('Y-m-d'),
                'customerNotes' => 'Customer Notes',
            ],
            [
                self::ORDER_PO_NUMBER,
                'Customer Notes',
                $this->dateFormatter->formatDate($date),
            ]
        );
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
        $product = $this->getReference('product.1');

        $date = (new \DateTime('now'))->format('Y-m-d');

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
                'quantity' => 10,
                'productUnit' => 'orob2b.product_unit.liter.label.full',
                'price' => $this->getContainer()->get('oro_locale.formatter.number')->formatCurrency(
                    $productPrice->getPrice()->getValue(),
                    $productPrice->getPrice()->getCurrency()
                ),
                'shipBy' => $date,
            ],
        ];

        $actualLineItems = [
            [
                'product' => $crawler->filter('input[name="orob2b_order_frontend_type[lineItems][0][product]"]')
                    ->extract('value')[0],
                'quantity' => $crawler->filter('input[name="orob2b_order_frontend_type[lineItems][0][quantity]"]')
                    ->extract('value')[0],
                'productUnit' => $crawler
                    ->filter('select[name="orob2b_order_frontend_type[lineItems][0][productUnit]"] :selected')
                    ->html(),
                'price' => trim($crawler->filter('.order-line-item-price-value')->html()),
                'shipBy' => $crawler->filter('input[name="orob2b_order_frontend_type[lineItems][0][shipBy]"]')
                    ->extract('value')[0],
            ],
        ];

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
     * @param array $orderData
     * @param array $expectedViewData
     */
    protected function assertOrderSave(Crawler $crawler, array $orderData, array $expectedViewData = null)
    {
        $formData = $this->getFormData($orderData);
        $form = $crawler->selectButton('Save and Close')->form($formData);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Order has been saved', $html);

        if ($expectedViewData) {
            $this->assertViewPage($crawler, $expectedViewData);
        }
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
}

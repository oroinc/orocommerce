<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Component\Testing\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\OrderBundle\Form\Type\FrontendOrderType;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
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
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $date = new \DateTime();
        $this->assertOrderSave(
            $crawler,
            [
                'poNumber' => 'PO-NUMBER',
                'shipUntil' => $date->format('Y-m-d'),
                'customerNotes' => 'Customer Notes'
            ],
            [
                'PO-NUMBER',
                'Customer Notes',
                $this->dateFormatter->formatDate($date)
            ]
        );
    }

    /**
     * @depends testCreate
     *
     * @return int
     */
    public function testUpdate()
    {
        $response = $this->requestFrontendGrid(
            'frontend-orders-grid',
            [
                'orders-grid[_filter][poNumber][value]' => 'PO-NUMBER'
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $id      = $result['id'];
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_frontend_update', ['id' => $result['id']])
        );
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertOrderSave(
            $crawler,
            [
                'poNumber' => 'PO-NUMBER-UP'
            ],
            [
                'Customer Notes',
                'PO-NUMBER-UP'
            ]
        );

        return $id;
    }

    /**
     * @depends testUpdate
     *
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_frontend_view', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertViewPage($crawler, ['Customer Notes', 'PO-NUMBER-UP']);
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
}

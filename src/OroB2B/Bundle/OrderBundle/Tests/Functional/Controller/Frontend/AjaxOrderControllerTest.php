<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

/**
 * @dbIsolation
 */
class AjaxOrderControllerTest extends WebTestCase
{
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
                'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
            ]
        );
    }

    public function testNewOrderSubtotals()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_frontend_create')
        );
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertSubtotals($crawler);
    }

    public function testSubtotals()
    {
        $order = $this->getReference(LoadOrders::MY_ORDER);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_order_frontend_update', ['id' => $order->getId()])
        );
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertSubtotals($crawler, $order->getId());
    }

    /**
     * @param Crawler $crawler
     * @param null|int $id
     */
    protected function assertSubtotals(Crawler $crawler, $id = null)
    {
        $form = $crawler->selectButton('Save and Close')->form();

        $form->getFormNode()->setAttribute('action', $this->getUrl('orob2b_order_frontend_subtotals', ['id' => $id]));

        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('subtotals', $data);
        $this->assertArrayHasKey('subtotal', $data['subtotals']);
    }
}

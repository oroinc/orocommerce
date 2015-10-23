<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        if (!$this->client->getContainer()->hasParameter('orob2b_order.entity.order.class')) {
            $this->markTestSkipped('OrderBundle disabled');
        }

        $this->loadFixtures(
            [
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
            ]
        );
    }

    public function testCreateOrder()
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);
        $this->assertFalse($request->getRequestProducts()->isEmpty());

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_request_create_order', ['id' => $request->getId()])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 302);

        $crawler = $this->client->followRedirect();
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertStringStartsWith(
            $this->getUrl('orob2b_order_create'),
            $this->client->getRequest()->getRequestUri()
        );
        $this->assertEquals(true, $this->client->getRequest()->get(ProductDataStorage::STORAGE_KEY));

        $content = $crawler->filter('[data-ftid=orob2b_order_type_lineItems]')->html();
        foreach ($request->getRequestProducts() as $lineItem) {
            $this->assertContains($lineItem->getProduct()->getSku(), $content);
        }
    }
}

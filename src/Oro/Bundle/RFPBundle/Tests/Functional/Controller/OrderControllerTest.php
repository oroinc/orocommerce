<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OrderControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );
    }

    public function testCreateOrder()
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);
        $this->assertFalse($request->getRequestProducts()->isEmpty());

        $this->client->followRedirects();
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_rfp_request_create_order', ['id' => $request->getId()])
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertStringStartsWith(
            $this->getUrl('oro_order_create'),
            $this->client->getRequest()->getRequestUri()
        );
        $this->assertEquals(true, $this->client->getRequest()->get(ProductDataStorage::STORAGE_KEY));

        $content = $crawler->filter('[data-ftid=oro_order_type_lineItems]')->html();
        foreach ($request->getRequestProducts() as $lineItem) {
            static::assertStringContainsString($lineItem->getProduct()->getSku(), $content);

            foreach ($lineItem->getRequestProductItems() as $requestProductItem) {
                $nodes = $crawler->filter(
                    sprintf(
                        '[data-quantity=%s][data-unit=%s]',
                        $requestProductItem->getQuantity(),
                        $requestProductItem->getProductUnitCode()
                    )
                );

                $this->assertNotEmpty($nodes->count());
            }
        }

        $this->assertEquals(
            $request->getId(),
            $crawler->filter('[data-ftid=oro_order_type_sourceEntityId]')->attr('value')
        );

        $this->assertEquals(
            $request->getIdentifier(),
            $crawler->filter('[data-ftid=oro_order_type_sourceEntityIdentifier]')->attr('value')
        );

        $this->assertEquals(
            get_class($request),
            $crawler->filter('[data-ftid=oro_order_type_sourceEntityClass]')->attr('value')
        );

        $this->assertEquals(
            $request->getPoNumber(),
            $crawler->filter('[data-ftid=oro_order_type_poNumber]')->attr('value')
        );

        $this->assertEquals(
            $request->getNote(),
            $crawler->filter('[data-ftid=oro_order_type_customerNotes]')->text()
        );

        $this->assertEquals(
            $request->getShipUntil()->format('Y-m-d'),
            $crawler->filter('[data-ftid=oro_order_type_shipUntil]')->attr('value')
        );
    }
}

<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Controller;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class OrderControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        if (!$this->client->getContainer()->hasParameter('oro_order.entity.order.class')) {
            $this->markTestSkipped('OrderBundle disabled');
        }

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
            $this->assertContains($lineItem->getProduct()->getSku(), $content);

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
            ClassUtils::getClass($request),
            $crawler->filter('[data-ftid=oro_order_type_sourceEntityClass]')->attr('value')
        );
    }
}

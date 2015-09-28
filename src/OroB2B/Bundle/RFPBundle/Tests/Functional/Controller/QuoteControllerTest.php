<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Functional\Controller;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class QuoteControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());

        if (!$this->client->getContainer()->hasParameter('orob2b_sale.entity.quote.class')) {
            static::markTestSkipped('SaleBundle disabled');
        }

        $this->loadFixtures(
            [
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData',
                'OroB2B\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestProductItemsData',
            ]
        );
    }

    public function testCreateQuote()
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST2);

        static::assertFalse($request->getRequestProducts()->isEmpty());

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_rfp_quote_create', ['id' => $request->getId()])
        );

        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 302);

        $crawler = $this->client->followRedirect();
        static::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        static::assertStringStartsWith(
            $this->getUrl('orob2b_sale_quote_create'),
            $this->client->getRequest()->getRequestUri()
        );
        static::assertEquals(true, $this->client->getRequest()->get(ProductDataStorage::STORAGE_KEY));

        $lineItems = $crawler->filter('[data-ftid=orob2b_sale_quote_quoteProducts]');
        static::assertNotEmpty($lineItems);
        $content = $lineItems->html();
        foreach ($request->getRequestProducts() as $requestProduct) {
            static::assertContains($requestProduct->getProduct()->getSku(), $content);
        }
    }
}

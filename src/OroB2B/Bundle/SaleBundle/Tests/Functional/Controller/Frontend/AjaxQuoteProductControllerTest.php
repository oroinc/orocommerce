<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class AjaxQuoteProductControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
        ]);
    }

    public function testMatchQuoteProductOfferAction()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER3, LoadUserData::ACCOUNT1_USER3)
        );

        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->getQuoteProduct();

        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference(LoadQuoteData::UNIT2);
        $quantity = 2;

        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_sale_quote_frontend_quote_product_match_offer',
                ['id' => $quoteProduct->getId(), 'unit'=> $productUnit->getCode(), 'qty'=> $quantity]
            )
        );

        $response = $this->client->getResponse();

        $result = $this->getJsonResponseContent($response, 200);

        $this->assertArrayHasKey('offer', $result);
        $this->assertArrayHasKey('unit', $result['offer']);
        $this->assertArrayHasKey('qty', $result['offer']);
        $this->assertArrayHasKey('price', $result['offer']);

        $formatter = $this->getContainer()->get('oro_locale.formatter.number');
        $offer = $result['offer'];

        $this->assertEquals($productUnit->getCode(), $offer['unit']);
        $this->assertEquals($quantity, $offer['qty']);
        $this->assertEquals(
            $formatter->formatCurrency(LoadQuoteData::PRICE2, LoadQuoteData::CURRENCY1),
            $offer['price']
        );
    }

    /**
     * @return null|QuoteProduct
     */
    protected function getQuoteProduct()
    {
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);

        foreach ($quote->getQuoteProducts() as $quoteItem) {
            if ($quoteItem->getProductSku() === LoadQuoteData::PRODUCT1) {
                return $quoteItem;
            }
        }

        return null;
    }
}

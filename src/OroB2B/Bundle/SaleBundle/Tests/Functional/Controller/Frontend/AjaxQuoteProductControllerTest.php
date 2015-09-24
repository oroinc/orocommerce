<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller\Frontend;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

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

        $this->loadFixtures(
            [
                'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
            ]
        );
    }

    /**
     * @dataProvider offerDataProvider
     *
     * @param string $productSku
     * @param string $quoteProductOfferReference
     * @param string $unitCode
     * @param string $quantity
     * @param array $expected
     */
    public function testMatchQuoteProductOfferAction(
        $productSku,
        $quoteProductOfferReference,
        $unitCode,
        $quantity,
        array $expected = []
    ) {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadUserData::ACCOUNT1_USER3, LoadUserData::ACCOUNT1_USER3)
        );

        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->getQuoteProduct($productSku);

        /** @var QuoteProductOffer $quoteProductOffer */
        $quoteProductOffer = $this->getReference($quoteProductOfferReference);
        if ($expected) {
            $expected = array_merge_recursive(['id' => $quoteProductOffer->getId()], $expected);
        }

        $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_sale_quote_frontend_quote_product_match_offer',
                ['id' => $quoteProduct->getId(), 'unit' => $unitCode, 'qty' => $quantity]
            )
        );

        $response = $this->client->getResponse();

        $result = $this->getJsonResponseContent($response, 200);

        $this->assertInternalType('array', $result);

        if (!$expected) {
            $this->assertEquals([], $result);

            return;
        }

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('unit', $result);
        $this->assertArrayHasKey('qty', $result);
        $this->assertArrayHasKey('price', $result);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function offerDataProvider()
    {
        return [
            'valid' => [
                LoadQuoteData::PRODUCT1,
                'sale.quote.1.product.1.offer.2',
                'bottle',
                2,
                [
                    'unit' => 'bottle',
                    'qty' => 2,
                    'price' => '$2.00',
                ],
            ],
            'empty unit' => [LoadQuoteData::PRODUCT1, 'sale.quote.1.product.1.offer.2', null, 2],
            'empty quantity' => [LoadQuoteData::PRODUCT1, 'sale.quote.1.product.1.offer.2', 'bottle', null],
        ];
    }

    /**
     * @param string $productSku
     *
     * @return null|QuoteProduct
     */
    protected function getQuoteProduct($productSku)
    {
        /** @var Quote $quote */
        $quote = $this->getReference(LoadQuoteData::QUOTE1);

        foreach ($quote->getQuoteProducts() as $quoteItem) {
            if ($quoteItem->getProductSku() === $productSku) {
                return $quoteItem;
            }
        }

        return null;
    }
}

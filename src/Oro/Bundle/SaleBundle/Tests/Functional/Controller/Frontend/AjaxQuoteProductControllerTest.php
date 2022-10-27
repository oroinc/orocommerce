<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxQuoteProductControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadQuoteProductDemandData::class,
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
            $this->generateBasicAuthHeader(LoadCustomerUserData::EMAIL, LoadCustomerUserData::PASSWORD)
        );

        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->getQuoteProduct($productSku);

        /** @var QuoteProductOffer $quoteProductOffer */
        $quoteProductOffer = $this->getReference($quoteProductOfferReference);
        if ($expected) {
            $expected = array_merge_recursive(['id' => $quoteProductOffer->getId()], $expected);
        }

        /** @var QuoteDemand $quoteDemand */
        $quoteDemand = $this->getReference(LoadQuoteProductDemandData::QUOTE_DEMAND_1);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_sale_quote_frontend_quote_product_match_offer',
                [
                    'id' => $quoteProduct->getId(),
                    'demandId' => $quoteDemand->getId(),
                    'unit' => $unitCode,
                    'qty' => $quantity
                ]
            )
        );

        $response = $this->client->getResponse();

        $result = $this->getJsonResponseContent($response, 200);

        $this->assertIsArray($result);
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
                'sale.quote.1.product-1.offer.2',
                'bottle',
                2,
                [
                    'unit' => 'bottle',
                    'qty' => 2,
                    'price' => '$2.00',
                ],
            ],
            'empty unit' => [LoadQuoteData::PRODUCT1, 'sale.quote.1.product-1.offer.2', null, 2],
            'empty quantity' => [LoadQuoteData::PRODUCT1, 'sale.quote.1.product-1.offer.2', 'bottle', null],
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

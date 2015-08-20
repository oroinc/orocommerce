<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
abstract class AbstractAjaxProductPriceControllerTest extends WebTestCase
{
    /** @var  string */
    protected $pricesByPriceListActionUrl;

    /**
     * @dataProvider getProductPricesByPriceListActionDataProvider
     * @param string $product
     * @param string $priceList
     * @param array $expected
     * @param string|null $currency
     */
    public function testGetProductPricesByPriceListAction($product, $priceList, array $expected, $currency = null)
    {
        /** @var Product $product */
        $product = $this->getReference($product);

        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);

        $params = [
            'price_list_id' => $priceList->getId(),
            'product_ids' => [$product->getId()]
        ];

        if ($currency) {
            $params['currency'] = $currency;
        }

        $this->client->request('GET', $this->getUrl($this->pricesByPriceListActionUrl, $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $expectedData = [
            $product->getId() => $expected
        ];

        $this->assertEquals($expectedData, $data);
    }
}

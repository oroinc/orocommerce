<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractAjaxProductPriceControllerTest extends WebTestCase
{
    /**
     * @var  string
     */
    protected $pricesByCustomerActionUrl;

    /**
     * @var string
     */
    protected $matchingPriceActionUrl;

    /**
     * @dataProvider getProductPricesByCustomerActionDataProvider
     *
     * @param string $product
     * @param array $expected
     * @param string|null $currency
     * @param string|null $customer
     * @param string|null $website
     */
    public function testGetProductPricesByCustomerAction(
        $product,
        array $expected,
        $currency = null,
        $customer = null,
        $website = null
    ) {
        /** @var Product $product */
        $product = $this->getReference($product);

        $params = [
            'product_ids' => [$product->getId()]
        ];
        if ($currency) {
            $params['currency'] = $currency;
        }
        if ($customer) {
            $params[ProductPriceScopeCriteriaRequestHandler::CUSTOMER_ID_KEY] = $this->getReference($customer)->getId();
        }
        if ($website) {
            $params[ProductPriceScopeCriteriaRequestHandler::WEBSITE_KEY] = $this->getReference($website)->getId();
        }

        $url = $this->getUrl($this->pricesByCustomerActionUrl, $params);
        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey($product->getId(), $data);
        $actualData = $data[$product->getId()];

        $actualDataByUnits = [];
        foreach ($actualData as $price) {
            $actualDataByUnits[$price['unit']][] = $price;
        }

        $expectedByUnits = [];
        foreach ($expected as $price) {
            $price['product_id'] = $product->getId();
            $expectedByUnits[$price['unit']][] = $price;
        }

        foreach ($expectedByUnits as $unit => $prices) {
            $this->assertArrayHasKey($unit, $actualDataByUnits);
            $this->assertCount(count($prices), $actualDataByUnits[$unit]);
            foreach ($prices as $price) {
                static::assertContainsEquals($price, $actualDataByUnits[$unit]);
            }
        }
    }

    /**
     * @dataProvider getMatchingPriceActionDataProvider
     * @param string $product
     * @param float|int $qty
     * @param string $unit
     * @param string $currency
     * @param array $expected
     */
    public function testGetMatchingPriceAction($product, $qty, $unit, $currency, array $expected)
    {
        /** @var Product $product */
        $product = $this->getReference($product);

        $params = [
            'items' => [
                ['qty' => $qty, 'product' => $product->getId(), 'unit' => $unit, 'currency' => $currency]
            ]
        ];

        $this->client->request('GET', $this->getUrl($this->matchingPriceActionUrl, $params));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $expectedData = [];
        if (0 !== count($expected)) {
            $expectedData = [
                $product->getId() .'-'. $unit .'-'. $qty .'-'. $currency  => $expected
            ];
        }

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    abstract public function getProductPricesByCustomerActionDataProvider();

    /**
     * @return array
     */
    public function getMatchingPriceActionDataProvider()
    {
        return [
            [
                'product' => 'product-1',
                'qty' => 0.1,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => []
            ],
            [
                'product' => 'product-1',
                'qty' => 1,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => [
                    'value' => 10,
                    'currency' => 'USD'
                ]
            ],
            [
                'product' => 'product-1',
                'qty' => 10,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => [
                    'value' => 12.2,
                    'currency' => 'USD'
                ]
            ],
            [
                'product' => 'product-1',
                'qty' => 120,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => [
                    'value' => 12.2,
                    'currency' => 'USD'
                ]
            ]
        ];
    }
}

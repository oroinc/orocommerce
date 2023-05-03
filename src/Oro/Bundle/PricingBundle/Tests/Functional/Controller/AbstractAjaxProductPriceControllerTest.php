<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

abstract class AbstractAjaxProductPriceControllerTest extends WebTestCase
{
    protected string $pricesByCustomerActionUrl;
    protected string $matchingPriceActionUrl;

    /**
     * @dataProvider getProductPricesByCustomerActionDataProvider
     */
    public function testGetProductPricesByCustomerAction(
        string $product,
        array $expected,
        string $currency = null,
        string $customer = null,
        string $website = null
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

        $data = self::jsonToArray($result->getContent());

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
                self::assertContainsEquals($price, $actualDataByUnits[$unit]);
            }
        }
    }

    /**
     * @dataProvider getMatchingPriceActionDataProvider
     */
    public function testGetMatchingPriceAction(
        string $product,
        float|int $qty,
        string $unit,
        string $currency,
        array $expected
    ) {
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

        $expectedData = [];
        if (0 !== count($expected)) {
            $expectedData = [
                $product->getId() .'-'. $unit .'-'. $qty .'-'. $currency  => $expected
            ];
        }

        $data = self::jsonToArray($result->getContent());
        $this->assertEquals($expectedData, $data);
    }

    abstract public function getProductPricesByCustomerActionDataProvider(): array;

    public function getMatchingPriceActionDataProvider(): array
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

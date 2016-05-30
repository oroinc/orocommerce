<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandlerInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
abstract class AbstractAjaxProductPriceControllerTest extends WebTestCase
{
    /**
     * @var  string
     */
    protected $pricesByAccountActionUrl;

    /**
     * @var string
     */
    protected $matchingPriceActionUrl;

    /**
     * @dataProvider getProductPricesByAccountActionDataProvider
     *
     * @param string $product
     * @param array $expected
     * @param string|null $currency
     * @param string|null $account
     * @param string|null $website
     */
    public function testGetProductPricesByAccountAction(
        $product,
        array $expected,
        $currency = null,
        $account = null,
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
        if ($account) {
            $params[PriceListRequestHandlerInterface::ACCOUNT_ID_KEY] = $this->getReference($account)->getId();
        }
        if ($website) {
            $params[PriceListRequestHandlerInterface::WEBSITE_KEY] = $this->getReference($website)->getId();
        }

        $url = $this->getUrl($this->pricesByAccountActionUrl, $params);
        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey($product->getId(), $data);
        $actualData = $data[$product->getId()];

        foreach ($expected as $unit => $prices) {
            $this->assertArrayHasKey($unit, $actualData);
            $this->assertCount(count($prices), $actualData[$unit]);
            foreach ($prices as $price) {
                $this->assertContains($price, $actualData[$unit]);
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
    abstract public function getProductPricesByAccountActionDataProvider();

    /**
     * @return array
     */
    public function getMatchingPriceActionDataProvider()
    {
        return [
            [
                'product' => 'product.1',
                'qty' => 0.1,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => []
            ],
            [
                'product' => 'product.1',
                'qty' => 1,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => [
                    'value' => 10,
                    'currency' => 'USD'
                ]
            ],
            [
                'product' => 'product.1',
                'qty' => 10,
                'unit' => 'liter',
                'currency' => 'USD',
                'expected' => [
                    'value' => 12.2,
                    'currency' => 'USD'
                ]
            ],
            [
                'product' => 'product.1',
                'qty' => 100,
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

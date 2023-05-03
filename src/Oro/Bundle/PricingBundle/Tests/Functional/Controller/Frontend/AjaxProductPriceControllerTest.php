<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\PricingBundle\Tests\Functional\Controller\AbstractAjaxProductPriceControllerTest;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;

class AjaxProductPriceControllerTest extends AbstractAjaxProductPriceControllerTest
{
    protected string $pricesByCustomerActionUrl = 'oro_pricing_frontend_price_by_customer';
    protected string $matchingPriceActionUrl = 'oro_pricing_frontend_matching_price';

    /** @var Client */
    protected $client;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([LoadCombinedProductPrices::class]);
    }

    /**
     * {@inheritDoc}
     */
    public function getProductPricesByCustomerActionDataProvider(): array
    {
        return [
            'without currency (all available currencies)' => [
                'product' => 'product-1',
                'expected' => [
                    ['price' => 10.0000, 'currency' => 'USD', 'quantity' => 1, 'unit' => 'liter'],
                    ['price' => 12.2000, 'currency' => 'USD', 'quantity' => 10, 'unit' => 'liter'],
                    ['price' => 13.1, 'currency' => 'USD', 'quantity' => 1, 'unit' => 'bottle'],
                ],
            ],
            'with currency' => [
                'product' => 'product-1',
                'expected' => [
                    ['price' => 10.0000, 'currency' => 'USD', 'quantity' => 1, 'unit' => 'liter'],
                    ['price' => 12.2000, 'currency' => 'USD', 'quantity' => 10, 'unit' => 'liter'],
                    ['price' => 13.1, 'currency' => 'USD', 'quantity' => 1, 'unit' => 'bottle'],
                ],
                'currency' => 'USD'
            ]
        ];
    }

    /**
     * @dataProvider setCurrentCurrencyDataProvider
     */
    public function testSetCurrentCurrencyAction(string $currency, array $expectedResult)
    {
        $params = ['currency' => $currency];
        $this->ajaxRequest('POST', $this->getUrl('oro_pricing_frontend_set_current_currency'), $params);
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = self::jsonToArray($result->getContent());
        $this->assertSame($expectedResult, $data);
    }

    public function setCurrentCurrencyDataProvider(): array
    {
        return [
            [
                'currency' => 'USD',
                'expectedResult' => ['success' => true]
            ],
            [
                'currency' => 'USD2',
                'expectedResult' => ['success' => false]
            ],
        ];
    }
}

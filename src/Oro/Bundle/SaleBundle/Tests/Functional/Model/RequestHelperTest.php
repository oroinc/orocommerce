<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Model;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestHelperTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadRequestData',
            ]
        );
    }

    /**
     * @dataProvider getRequestsWoQuoteDataProvider
     *
     * @param int $days
     * @param array $expected
     */
    public function testGetRequestsWoQuote($days, array $expected)
    {
        $expectedRequests = [];
        foreach ($expected as $item) {
            $expectedRequests[] = $this->getReference($item);
        }

        $this->assertEquals(
            $expectedRequests,
            $this->getContainer()->get('oro_sale.service.request_helper')->getRequestsWoQuote($days)
        );
    }

    /**
     * @return array
     */
    public function getRequestsWoQuoteDataProvider()
    {
        return [
            'current date' => [
                'days' => 0,
                'expected' => [
                    LoadRequestData::REQUEST_WITHOUT_QUOTE,
                    LoadRequestData::REQUEST_WITHOUT_QUOTE_OLD
                ],
            ],
            '2 days' => [
                'days' => 2,
                'expected' => [
                    LoadRequestData::REQUEST_WITHOUT_QUOTE_OLD
                ],
            ],
            '6 days' => [
                'days' => 6,
                'expected' => [
                ],
            ],
        ];
    }
}

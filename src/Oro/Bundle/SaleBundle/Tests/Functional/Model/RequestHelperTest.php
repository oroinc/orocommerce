<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Model;

use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RequestHelperTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadRequestData::class]);
    }

    /**
     * @dataProvider getRequestsWoQuoteDataProvider
     */
    public function testGetRequestsWoQuote(int $days, array $expected)
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

    public function getRequestsWoQuoteDataProvider(): array
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
                'expected' => [],
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Model;

use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RequestHelperTest extends WebTestCase
{
    #[\Override]
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
        $actualRequests = $this->getContainer()->get('oro_sale.service.request_helper')->getRequestsWoQuote($days);
        $this->assertCount(count($expected), $actualRequests);

        foreach ($expected as $item) {
            $expectedRequest = $this->getReference($item);
            $actualRequest = null;
            foreach ($actualRequests as $j => $actualRequest) {
                if ($item === $actualRequest->getNote()) {
                    unset($actualRequests[$j]);
                    break;
                }
            }
            $this->assertNotNull($actualRequest);
            $this->assertEquals($expectedRequest, $actualRequest);
        }
        $this->assertEmpty($actualRequests);
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

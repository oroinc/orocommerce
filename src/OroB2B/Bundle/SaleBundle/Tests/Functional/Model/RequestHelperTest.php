<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\SaleBundle\Model\RequestHelper;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolation
 */
class RequestHelperTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadRequestData',
        ]);
        $this->requestHelper = new RequestHelper(
            static::getContainer()->get('doctrine'),
            'OroB2BSaleBundle:Quote',
            'OroB2BRFPBundle:Request'
        );
    }

    /**
     * @dataProvider getRequestsWoQuoteDataProvider
     * @param int $days
     * @param array $expected
     */
    public function testGetRequestsWoQuote($days, $expected)
    {
        $expectedRequests = [];
        foreach ($expected as $item) {
            $expectedRequests[] = $this->getReference($item);
        }
        $this->assertEquals($expectedRequests, $this->requestHelper->getRequestsWoQuote($days));
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
            '5 days' => [
                'days' => 5,
                'expected' => [
                ],
            ],
        ];
    }
}

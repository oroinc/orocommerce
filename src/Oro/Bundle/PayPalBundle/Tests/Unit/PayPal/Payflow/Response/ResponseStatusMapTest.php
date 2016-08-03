<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Response;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;

class ResponseStatusMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider testGetMessageByStatusDataProvider
     * @param string $status
     * @param string $message
     */
    public function testGetMessageByStatus($status, $message)
    {
        $this->assertSame($message, ResponseStatusMap::getMessage($status));
    }

    /**
     * @return array
     */
    public function testGetMessageByStatusDataProvider()
    {
        return [
            [
                'status' => ResponseStatusMap::APPROVED,
                'message' => 'Approved'
            ],
            [
                'status' => ResponseStatusMap::DUPLICATE_TRANSACTION,
                'message' => 'Duplicate transaction'
            ],
            [
                'status' => ResponseStatusMap::CREDIT_ERROR,
                'message' => 'Credit error.'
            ],
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Not supported response status code
     */
    public function testInvalidStatus()
    {
        ResponseStatusMap::getMessage('1555445');
    }
}

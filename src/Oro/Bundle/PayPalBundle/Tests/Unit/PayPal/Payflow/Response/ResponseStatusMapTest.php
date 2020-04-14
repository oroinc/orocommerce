<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Response;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;

class ResponseStatusMapTest extends \PHPUnit\Framework\TestCase
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

    public function testInvalidStatus()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not supported response status code');

        ResponseStatusMap::getMessage('1555445');
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Response;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;

class ResponseStatusMapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getMessageByStatusDataProvider
     */
    public function testGetMessageByStatus(string $status, string $message)
    {
        $this->assertSame($message, ResponseStatusMap::getMessage($status));
    }

    public function getMessageByStatusDataProvider(): array
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

<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Response;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\CommunicationErrorsStatusMap;

class CommunicationErrorsStatusMapTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getMessageByStatusDataProvider
     */
    public function testGetMessageByStatus(string $status, string $message)
    {
        $this->assertSame($message, CommunicationErrorsStatusMap::getMessage($status));
    }

    public function getMessageByStatusDataProvider(): array
    {
        return [
            [
                'status' => CommunicationErrorsStatusMap::FAILED_TO_INITIALIZE_SSL_CONTEXT,
                'message' => 'Failed to initialize SSL context'
            ],
            [
                'status' => CommunicationErrorsStatusMap::HOST_ADDRESS_NOT_SPECIFIED,
                'message' => 'Host address not specified'
            ],
            [
                'status' => CommunicationErrorsStatusMap::INVALID_TRANSACTION_TYPE,
                'message' => 'Invalid transaction type'
            ],
        ];
    }

    public function testInvalidStatus()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Not supported response status code');

        CommunicationErrorsStatusMap::getMessage('1555445');
    }
}

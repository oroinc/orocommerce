<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Response;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\CommunicationErrorsStatusMap;

class CommunicationErrorsStatusMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider testGetMessageByStatusDataProvider
     * @param string $status
     * @param string $message
     */
    public function testGetMessageByStatus($status, $message)
    {
        $this->assertSame($message, CommunicationErrorsStatusMap::getMessage($status));
    }

    /**
     * @return array
     */
    public function testGetMessageByStatusDataProvider()
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Not supported response status code
     */
    public function testInvalidStatus()
    {
        CommunicationErrorsStatusMap::getMessage('1555445');
    }
}

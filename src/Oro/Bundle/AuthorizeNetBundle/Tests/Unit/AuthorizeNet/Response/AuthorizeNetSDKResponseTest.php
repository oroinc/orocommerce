<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Response;

use JMS\Serializer\Serializer;
use net\authorize\api\contract\v1\MessagesType;
use net\authorize\api\contract\v1\TransactionResponseType\MessagesAType\MessageAType as TransactionMessage;
use net\authorize\api\contract\v1\TransactionResponseType\ErrorsAType\ErrorAType as TransactionErrorMessage;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\TransactionResponseType;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\AuthorizeNetSDKResponse;

class AuthorizeNetSDKResponseTest extends \PHPUnit_Framework_TestCase
{
    /** @var Serializer|\PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var CreateTransactionResponse|\PHPUnit_Framework_MockObject_MockObject */
    protected $apiResponse;

    /** @var AuthorizeNetSDKResponse */
    protected $authorizeNetSdkResponse;

    protected function setUp()
    {
        $this->serializer = $this->createMock(Serializer::class);
        $this->apiResponse = $this->createMock(CreateTransactionResponse::class);
        $this->authorizeNetSdkResponse = new AuthorizeNetSDKResponse($this->serializer, $this->apiResponse);
    }

    public function testIsSuccessfulWithNullableTransactionResponse()
    {
        $this->apiResponse->expects($this->once())->method('getTransactionResponse')->willReturn(null);
        $this->assertFalse($this->authorizeNetSdkResponse->isSuccessful());
    }

    public function testIsSuccessfulWithZeroResponseCode()
    {
        $transactionResponse = new TransactionResponseType();
        $transactionResponse->setResponseCode(0);
        $this->apiResponse->expects($this->once())->method('getTransactionResponse')
            ->willReturn($transactionResponse);

        $this->assertFalse($this->authorizeNetSdkResponse->isSuccessful());
    }

    public function testIsSuccessfulWithIntegerResponse()
    {
        $transactionResponse = new TransactionResponseType();
        $transactionResponse->setResponseCode(1);
        $this->apiResponse->expects($this->once())->method('getTransactionResponse')
            ->willReturn($transactionResponse);

        $this->assertFalse($this->authorizeNetSdkResponse->isSuccessful());
    }

    public function testIsSuccessfulWithValidResponse()
    {
        $transactionResponse = new TransactionResponseType();
        $transactionResponse->setResponseCode('1');
        $this->apiResponse->expects($this->once())->method('getTransactionResponse')
            ->willReturn($transactionResponse);

        $this->assertTrue($this->authorizeNetSdkResponse->isSuccessful());
    }

    public function testGetReferenceWithEmptyTransactionResponse()
    {
        $this->apiResponse->expects($this->once())->method('getTransactionResponse')->willReturn(null);
        $this->assertNull($this->authorizeNetSdkResponse->getReference());
    }

    public function testGetReferenceWithValidResponse()
    {
        $transId = '111';
        $transactionResponse = new TransactionResponseType();
        $transactionResponse->setTransId($transId);
        $this->apiResponse->expects($this->once())->method('getTransactionResponse')
            ->willReturn($transactionResponse);

        $this->assertSame($transId, $this->authorizeNetSdkResponse->getReference());
    }

    public function testGetSuccessMessage()
    {
        $transactionMessage = (new TransactionMessage)->setCode(144)->setDescription('Luke is the best jedi');

        $transactionResponse = new TransactionResponseType();
        $transactionResponse->setResponseCode('1');
        $transactionResponse->setMessages([$transactionMessage]);

        $apiMessage = (new MessagesType\MessageAType)->setCode(255)->setText('Will be force with you!');
        $apiMessageType = (new MessagesType)->setMessage([$apiMessage]);

        $this->apiResponse->expects($this->once())->method('getMessages')->willReturn($apiMessageType);
        $this->apiResponse->expects($this->exactly(2))->method('getTransactionResponse')
            ->willReturn($transactionResponse);

        $this->assertSame(
            '(255) Will be force with you!;  (144) Luke is the best jedi',
            $this->authorizeNetSdkResponse->getMessage()
        );
    }

    public function testGetErrorMessage()
    {
        $transactionError = (new TransactionErrorMessage)->setErrorCode(125)
            ->setErrorText('Darth Vader is coming for you');

        $transactionResponse = new TransactionResponseType();
        $transactionResponse->setResponseCode('0');
        $transactionResponse->setErrors([$transactionError]);

        $apiMessage = (new MessagesType\MessageAType)->setCode(408)->setText('The Dark Side is strong in you!');
        $apiMessageType = (new MessagesType)->setMessage([$apiMessage]);

        $this->apiResponse->expects($this->once())->method('getMessages')->willReturn($apiMessageType);
        $this->apiResponse->expects($this->exactly(2))->method('getTransactionResponse')
            ->willReturn($transactionResponse);

        $this->assertSame(
            '(408) The Dark Side is strong in you!;  (125) Darth Vader is coming for you',
            $this->authorizeNetSdkResponse->getMessage()
        );
    }

    /**
     * @dataProvider responseArrayDataProvider
     * @param $entryData
     * @param $expectedData
     */
    public function testFetData($entryData, $expectedData)
    {
        $this->serializer->expects($this->once())->method('toArray')
            ->with($this->apiResponse)->willReturn($entryData);

        $this->assertSame($expectedData, $this->authorizeNetSdkResponse->getData());
    }

    /**
     * @return array
     */
    public function responseArrayDataProvider()
    {
        return [
            [
                'entry_data' => [
                    'transId' => 1,
                    'responseCode' => 2,
                    'message' => ['success' => 'this is success message', 'empty_array' => []],
                    'empty_row' => '',
                    'empty_array' => [],
                    'zero_value' => 0,
                ],
                'expected_data' => [
                    'transId' => 1,
                    'responseCode' => 2,
                    'message' => ['success' => 'this is success message'],
                    'zero_value' => 0,
                ],
            ],
        ];
    }
}

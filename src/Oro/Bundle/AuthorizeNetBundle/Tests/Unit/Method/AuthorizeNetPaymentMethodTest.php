<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Method;

use JMS\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\MessagesType;
use net\authorize\api\contract\v1\TransactionResponseType;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\AuthorizeNetSDKResponse;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option\Transaction;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Gateway;
use Oro\Bundle\AuthorizeNetBundle\Method\AuthorizeNetPaymentMethod;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AuthorizeNetPaymentMethodTest extends \PHPUnit_Framework_TestCase
{
    /** @var Gateway|\PHPUnit_Framework_MockObject_MockObject */
    protected $gateway;

    /** @var AuthorizeNetPaymentMethod */
    protected $method;

    /** @var AuthorizeNetConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentConfig;

    /** @var Serializer|\PHPUnit_Framework_MockObject_MockObject */
    protected $serializer;

    /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    protected function setUp()
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->paymentConfig = $this->createMock(AuthorizeNetConfigInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->method = new AuthorizeNetPaymentMethod($this->gateway, $this->paymentConfig, $this->requestStack);

        $this->serializer = $this->createMock(Serializer::class);
    }

    /**
     * @dataProvider purchaseExecuteProvider
     * @param string $purchaseAction
     * @param string $gatewayTransactionType
     * @param bool $requestSuccessful
     * @param string $expectedMessage
     * @param string|null $transId
     * @param array $responseArray
     */
    public function testPurchaseExecute(
        $purchaseAction,
        $gatewayTransactionType,
        $requestSuccessful,
        $expectedMessage,
        $transId,
        array $responseArray
    ) {
        $testMode = false;
        $transaction = $this->createPaymentTransaction(PaymentMethodInterface::PURCHASE);

        $this->paymentConfig->expects($this->any())
            ->method('isTestMode')
            ->willReturn($testMode);

        $this->paymentConfig->expects($this->any())
            ->method('getPurchaseAction')
            ->willReturn($purchaseAction);

        $this->gateway->expects($this->once())
            ->method('setTestMode')
            ->with($testMode);

        $response = $this->prepareSDKResponse($requestSuccessful);

        $this->gateway->expects($this->once())
            ->method('request')
            ->with($gatewayTransactionType)
            ->willReturn($response);

        $this->assertEquals(
            [
                'message' => $expectedMessage,
                'successful' => $requestSuccessful,
            ],
            $this->method->execute($transaction->getAction(), $transaction)
        );

        $this->assertSame($requestSuccessful, $transaction->isSuccessful());
        $this->assertSame($requestSuccessful, $transaction->isActive());
        $this->assertSame($transId, $transaction->getReference());
        $this->assertSame($responseArray, $transaction->getResponse());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported action "wrong_action"
     */
    public function testExecuteException()
    {
        $transaction = new PaymentTransaction();
        $transaction->setAction('wrong_action');

        $this->method->execute($transaction->getAction(), $transaction);
    }


    /**
     * @dataProvider executeProvider
     * @param string $paymentAction
     * @param string $gatewayTransactionType
     * @param bool $requestSuccessful
     * @param string $expectedMessage
     * @param string|null $transId
     * @param array $responseArray
     */
    public function testExecute(
        $paymentAction,
        $gatewayTransactionType,
        $requestSuccessful,
        $expectedMessage,
        $transId,
        array $responseArray
    ) {
        $testMode = false;
        $transaction = $this->createPaymentTransaction($paymentAction);

        $this->paymentConfig->expects($this->any())
            ->method('isTestMode')
            ->willReturn($testMode);

        $this->gateway->expects($this->once())
            ->method('setTestMode')
            ->with($testMode);

        $response = $this->prepareSDKResponse($requestSuccessful);

        $this->gateway->expects($this->once())
            ->method('request')
            ->with($gatewayTransactionType)
            ->willReturn($response);

        $this->assertEquals(
            [
                'message' => $expectedMessage,
                'successful' => $requestSuccessful,
            ],
            $this->method->execute($transaction->getAction(), $transaction)
        );

        $this->assertSame($requestSuccessful, $transaction->isSuccessful());
        $this->assertSame($requestSuccessful, $transaction->isActive());
        $this->assertSame($transId, $transaction->getReference());
        $this->assertSame($responseArray, $transaction->getResponse());
    }

    /**
     * @return array
     */
    public function executeProvider()
    {
        return [
            'successful charge' => [
                'paymentAction' => PaymentMethodInterface::CHARGE,
                'gatewayTransactionType' => Transaction::CHARGE,
                'requestSuccessful' => true,
                'expectedMessage' => '(1) success',
                'transId' => '111',
                'responseArray' => ['1', 'success', '111'],
            ],
            'successful authorize' => [
                'paymentAction' => PaymentMethodInterface::AUTHORIZE,
                'gatewayTransactionType' => Transaction::AUTHORIZE,
                'requestSuccessful' => true,
                'expectedMessage' => '(1) success',
                'transId' => '111',
                'responseArray' => ['1', 'success', '111'],
            ],
        ];
    }

    public function testCapture()
    {
        $authorizeTransaction = $this->createPaymentTransaction(PaymentMethodInterface::AUTHORIZE);
        $transaction = (new PaymentTransaction)
            ->setSourcePaymentTransaction($authorizeTransaction)
            ->setAction(PaymentMethodInterface::CAPTURE);

        $testMode = false;

        $this->paymentConfig->expects($this->any())
            ->method('isTestMode')
            ->willReturn($testMode);

        $this->paymentConfig->expects($this->any())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::CAPTURE);

        $this->gateway->expects($this->once())
            ->method('setTestMode')
            ->with($testMode);

        $response = $this->prepareSDKResponse(true);

        $this->gateway->expects($this->once())
            ->method('request')
            ->with(Transaction::CAPTURE)
            ->willReturn($response);

        $result = $this->method->execute($transaction->getAction(), $transaction);
        $this->assertArrayHasKey('message', $result);
        $this->assertSame('(1) success', $result['message']);

        $this->assertArrayHasKey('successful', $result);
        $this->assertTrue($result['successful']);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
        $this->assertNotNull($transaction->getSourcePaymentTransaction());
        $this->assertFalse($transaction->getSourcePaymentTransaction()->isActive());
    }

    public function testValidate()
    {
        $validateTransaction = $this->createPaymentTransaction(PaymentMethodInterface::VALIDATE);

        $testMode = false;

        $this->paymentConfig->expects($this->any())
            ->method('isTestMode')
            ->willReturn($testMode);

        $this->gateway->expects($this->once())
            ->method('setTestMode')
            ->with($testMode);

        $result = $this->method->execute($validateTransaction->getAction(), $validateTransaction);

        $this->assertArrayHasKey('successful', $result);
        $this->assertTrue($result['successful']);

        $this->assertTrue($validateTransaction->isSuccessful());
        $this->assertTrue($validateTransaction->isActive());
        $this->assertEquals(PaymentMethodInterface::VALIDATE, $validateTransaction->getAction());
        $this->assertEquals(0, $validateTransaction->getAmount());
        $this->assertEquals('', $validateTransaction->getCurrency());
    }

    public function testCaptureWithoutSourcePaymentAction()
    {
        $transaction = (new PaymentTransaction)
            ->setAction(PaymentMethodInterface::CAPTURE);

        $testMode = false;

        $this->paymentConfig->expects($this->any())
            ->method('isTestMode')
            ->willReturn($testMode);

        $this->gateway->expects($this->once())
            ->method('setTestMode')
            ->with($testMode);

        $this->gateway->expects($this->never())
            ->method('request');

        $result = $this->method->execute($transaction->getAction(), $transaction);
        $this->assertArrayHasKey('successful', $result);
        $this->assertFalse($result['successful']);

        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
    }

    /**
     * @dataProvider incorrectAdditionalDataProvider
     * @param string $expectedExceptionMessage
     * @param array|null $transactionOptions
     * @expectedException \LogicException
     */
    public function testIncorrectAdditionalData($expectedExceptionMessage, array $transactionOptions = null)
    {
        $this->expectExceptionMessage($expectedExceptionMessage);

        $transaction = $this->createPaymentTransaction(PaymentMethodInterface::PURCHASE);
        $transaction->getSourcePaymentTransaction()->setTransactionOptions($transactionOptions);
        $this->method->execute($transaction->getAction(), $transaction);
    }

    public function testCorrectAdditionalData()
    {
        $transactionOptions = [
            'additionalData' => json_encode([
                'dataDescriptor' => 'dataDescriptorValue',
                'dataValue' => 'dataValueValue',
            ])
        ];

        $response = $this->prepareSDKResponse(true);
        $this->gateway->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->paymentConfig->expects($this->any())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);


        $transaction = $this->createPaymentTransaction(PaymentMethodInterface::PURCHASE);
        $transaction->getSourcePaymentTransaction()->setTransactionOptions($transactionOptions);
        $result = $this->method->execute($transaction->getAction(), $transaction);
        $this->assertInternalType('array', $result);
    }

    /**
     * @param bool $expected
     * @param string $actionName
     *
     * @dataProvider supportsDataProvider
     */
    public function testSupports($expected, $actionName)
    {
        $this->assertEquals($expected, $this->method->supports($actionName));
    }

    public function testIsApplicableWithValidRequest()
    {
        $isConnectionSecure = true;
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('isSecure')
            ->willReturn($isConnectionSecure);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertTrue($this->method->isApplicable($context));
    }

    public function testIsApplicableWithoutCurrentRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertTrue($this->method->isApplicable($context));
    }

    public function testGetIdentifier()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('authorize_net');

        $this->assertSame('authorize_net', $this->method->getIdentifier());
    }

    /**
     * @param bool $requestSuccessful
     * @return AuthorizeNetSDKResponse
     */
    protected function prepareSDKResponse($requestSuccessful)
    {
        $transactionResponse = new TransactionResponseType();
        if ($requestSuccessful === true) {
            $responseCode = '1';
            $message = 'success';
            $transactionId = '111';

            $transactionResponse->setMessages([]);
        } else {
            $responseCode = '0';
            $message = 'fail';
            $transactionId = null;

            $transactionResponse->setErrors([]);
        }

        $apiMessage = (new MessagesType\MessageAType)->setCode($responseCode)->setText($message);
        $apiMessageType = (new MessagesType)->setMessage([$apiMessage]);

        $transactionResponse->setResponseCode($responseCode);
        $transactionResponse->setTransId($transactionId);

        $apiResponse = $this->createMock(CreateTransactionResponse::class);
        $apiResponse->expects($this->once())
            ->method('getMessages')
            ->willReturn($apiMessageType);

        $apiResponse->expects($this->any())
            ->method('getTransactionResponse')
            ->willReturn($transactionResponse);

        $this->serializer->expects($this->once())
            ->method('toArray')
            ->with($apiResponse)
            ->willReturn([$responseCode, $message, $transactionId]);

        return new AuthorizeNetSDKResponse($this->serializer, $apiResponse);
    }

    /**
     * @param string $paymentAction
     * @return PaymentTransaction
     */
    protected function createPaymentTransaction($paymentAction)
    {
        $sourcePaymentTransaction = new PaymentTransaction();
        $additionalData = [
            'dataDescriptor' => 'data_descriptor_value',
            'dataValue' => 'data_value_value'
        ];

        $sourcePaymentTransaction
            ->setTransactionOptions(['additionalData' => json_encode($additionalData)]);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setAction($paymentAction)
            ->setSourcePaymentTransaction($sourcePaymentTransaction);

        return $paymentTransaction;
    }

    /**
     * @return array
     */
    public function purchaseExecuteProvider()
    {
        return [
            'successful charge' => [
                'purchaseAction' => PaymentMethodInterface::CHARGE,
                'gatewayTransactionType' => Transaction::CHARGE,
                'requestSuccessful' => true,
                'expectedMessage' => '(1) success',
                'transId' => '111',
                'responseArray' => ['1', 'success', '111'],
            ],
            'successful authorize' => [
                'purchaseAction' => PaymentMethodInterface::AUTHORIZE,
                'gatewayTransactionType' => Transaction::AUTHORIZE,
                'requestSuccessful' => true,
                'expectedMessage' => '(1) success',
                'transId' => '111',
                'responseArray' => ['1', 'success', '111'],
            ],
            'unsuccessful authorize' => [
                'purchaseAction' => PaymentMethodInterface::AUTHORIZE,
                'gatewayTransactionType' => Transaction::AUTHORIZE,
                'requestSuccessful' => false,
                'expectedMessage' => '(0) fail',
                'transId' => null,
                'responseArray' => ['0', 'fail', null],
            ],
        ];
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            [true, PaymentMethodInterface::AUTHORIZE],
            [true, PaymentMethodInterface::CAPTURE],
            [true, PaymentMethodInterface::CHARGE],
            [true, PaymentMethodInterface::PURCHASE],
            [true, PaymentMethodInterface::VALIDATE],
        ];
    }

    /**
     * @return array
     */
    public function incorrectAdditionalDataProvider()
    {
        return [
            'nullable transaction options' => [
                'expectedExceptionMessage' => 'Cant extract required opaque credit card credentials from transaction',
                'transactionOptions' => null,
            ],
            'empty array of transaction options' => [
                'expectedExceptionMessage' => 'Cant extract required opaque credit card credentials from transaction',
                'transactionOptions' => [],
            ],
            'nullable additional data' => [
                'expectedExceptionMessage' => 'Additional data must be an array',
                'transactionOptions' => ['additionalData' => null],
            ],
            'non-json additional data' => [
                'expectedExceptionMessage' => 'Additional data must be an array',
                'transactionOptions' => ['additionalData' => 'apiLoginId,transactionKey'],
            ],
            'json additional data only with dataDescriptor' => [
                'expectedExceptionMessage' => 'Can not find field "dataValue" in additional data',
                'transactionOptions' => ['additionalData' => json_encode(['dataDescriptor' => 'value'])]
            ],
            'json additional data only with dataValue' => [
                'expectedExceptionMessage' => 'Can not find field "dataDescriptor" in additional data',
                'transactionOptions' => ['additionalData' => json_encode(['dataValue' => 'value'])]
            ],
        ];
    }
}

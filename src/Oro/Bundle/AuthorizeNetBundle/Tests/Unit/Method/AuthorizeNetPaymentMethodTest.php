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

    /** @var  CreateTransactionResponse|\PHPUnit_Framework_MockObject_MockObject */
    protected $apiResponse;

    /** @var  RequestStack|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestStack;

    protected function setUp()
    {
        $this->gateway = $this->createMock(Gateway::class);

        $this->paymentConfig = $this->createMock(AuthorizeNetConfigInterface::class);

        $this->requestStack = $this->createMock(RequestStack::class);

        $this->method = new AuthorizeNetPaymentMethod($this->gateway, $this->paymentConfig, $this->requestStack);

        $this->serializer = $this->createMock(Serializer::class);

        $this->apiResponse = $this->createMock(CreateTransactionResponse::class);
    }

    /**
     * @dataProvider paymentActionDataProvider
     * @param string $paymentAction
     * @param string $authorizePaymentAction
     * @param bool $requestResult
     * @param string $expectedMessage
     * @param string|null $transId
     */
    public function testExecute(
        $paymentAction,
        $authorizePaymentAction,
        $requestResult,
        $expectedMessage,
        $transId,
        $responseArray
    ) {
        $transaction = $this->preparePaymentTransaction(PaymentMethodInterface::PURCHASE);

        $this->paymentConfig->expects($this->any())->method('isTestMode')->willReturn(false);
        $this->paymentConfig->expects($this->any())
            ->method('getPurchaseAction')->willReturn($paymentAction);

        $this->gateway->expects($this->once())->method('setTestMode')
            ->with($this->paymentConfig->isTestMode());
        $this->gateway->expects($this->once())->method('request')->with($authorizePaymentAction)
            ->willReturn($this->prepareSDKResponse($requestResult));

        $this->assertEquals(
            [
                'message' => $expectedMessage,
                'successful' => $requestResult,
            ],
            $this->method->execute($transaction->getAction(), $transaction)
        );

        $this->assertSame($requestResult, $transaction->isSuccessful());
        $this->assertSame($requestResult, $transaction->isActive());
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

    public function testCapture()
    {
        $sourceTransaction = $this->preparePaymentTransaction(PaymentMethodInterface::AUTHORIZE);
        $transaction = (new PaymentTransaction)
            ->setSourcePaymentTransaction($sourceTransaction)
            ->setAction(PaymentMethodInterface::CAPTURE);

        $this->paymentConfig->expects($this->any())->method('isTestMode')->willReturn(false);
        $this->paymentConfig->expects($this->any())
            ->method('getPurchaseAction')->willReturn(PaymentMethodInterface::CAPTURE);

        $this->gateway->expects($this->once())->method('setTestMode')
            ->with($this->paymentConfig->isTestMode());
        $this->gateway->expects($this->once())->method('request')->with(Transaction::CAPTURE)
            ->willReturn($this->prepareSDKResponse(true));

        $this->assertEquals(
            [
                'message' => '(1) success',
                'successful' => true,
            ],
            $this->method->execute($transaction->getAction(), $transaction)
        );

        $this->assertSame(true, $transaction->isSuccessful());
        $this->assertSame(false, $transaction->isActive());
        $this->assertSame(false, $transaction->getSourcePaymentTransaction()->isActive());
    }

    public function testCaptureWithoutSourcePaymentAction()
    {
        $transaction = (new PaymentTransaction)
            ->setAction(PaymentMethodInterface::CAPTURE);

        $this->paymentConfig->expects($this->any())->method('isTestMode')->willReturn(false);

        $this->gateway->expects($this->once())->method('setTestMode')
            ->with($this->paymentConfig->isTestMode());

        $this->gateway->expects($this->never())->method('request');

        $this->assertEquals(
            ['successful' => false],
            $this->method->execute($transaction->getAction(), $transaction)
        );

        $this->assertSame(false, $transaction->isSuccessful());
        $this->assertSame(false, $transaction->isActive());
    }

    /**
     * @dataProvider wrongCredentialsProvider
     * @param mixed $transactionOptions
     * @expectedException \LogicException
     * @expectedExceptionMessage Cant extract required opaque credit card credentials from transaction
     */
    public function testEmptyCredentials($transactionOptions)
    {
        $transaction = $this->preparePaymentTransaction(PaymentMethodInterface::PURCHASE);
        $transaction->getSourcePaymentTransaction()->setTransactionOptions($transactionOptions);
        $this->method->execute($transaction->getAction(), $transaction);
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
        $request->expects($this->once())->method('isSecure')->willReturn($isConnectionSecure);
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertSame($isConnectionSecure, $this->method->isApplicable($context));
    }

    public function testIsApplicableWithoutCurrentRequest()
    {
        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn(null);

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
     * @param bool $requestResult
     * @return AuthorizeNetSDKResponse
     */
    protected function prepareSDKResponse($requestResult = true)
    {
        $transactionResponse = new TransactionResponseType();
        if ($requestResult === true) {
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

        $this->apiResponse->expects($this->once())->method('getMessages')->willReturn($apiMessageType);
        $this->apiResponse->expects($this->any())->method('getTransactionResponse')
            ->willReturn($transactionResponse);

        $this->serializer->expects($this->once())
            ->method('toArray')
            ->with($this->apiResponse)
            ->willReturn([$responseCode, $message, $transactionId]);

        return new AuthorizeNetSDKResponse($this->serializer, $this->apiResponse);
    }

    /**
     * @param $paymentAction
     * @return PaymentTransaction
     */
    protected function preparePaymentTransaction($paymentAction)
    {
        $sourcePaymentTransaction = (new PaymentTransaction)
            ->setTransactionOptions(['additionalData' => 'apiLoginId;transKey']);

        return (new PaymentTransaction)
            ->setAction($paymentAction)
            ->setSourcePaymentTransaction($sourcePaymentTransaction);
    }

    /**
     * @return array
     */
    public function paymentActionDataProvider()
    {
        return [
            [
                'paymentAction' => PaymentMethodInterface::CHARGE,
                'authorizePaymentAction' => Transaction::CHARGE,
                'requestResult' => true,
                'expectedMessage' => '(1) success',
                'transId' => '111',
                'responseArray' => ['1', 'success', '111'],
            ],
            [
                'paymentAction' => PaymentMethodInterface::AUTHORIZE,
                'authorizePaymentAction' => Transaction::AUTHORIZE,
                'requestResult' => false,
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
    public function wrongCredentialsProvider()
    {
        return [
            [
                [],
            ],
            [
                ['additionalData' => null],
            ],
            [
                ['additionalData' => 'apiLoginId,transactionKey'],
            ],
        ];
    }
}

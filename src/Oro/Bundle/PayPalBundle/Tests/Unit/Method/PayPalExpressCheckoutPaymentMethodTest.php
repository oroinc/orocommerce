<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\EntityStub;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayPalExpressCheckoutPaymentMethod;
use Oro\Bundle\PayPalBundle\OptionsProvider\OptionsProviderInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\RouterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PayPalExpressCheckoutPaymentMethodTest extends \PHPUnit\Framework\TestCase
{
    const CONFIG_PREFIX = 'payflow_express_checkout_';
    const PRODUCTION_REDIRECT_URL = 'https://www.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';
    const PILOT_REDIRECT_URL = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';
    const TOKEN = 'token';
    const ENTITY_CLASS = 'EntityClass';
    const ENTITY_ID = 15689;
    const SHIPPING_COST = 1;
    const DISCOUNT_AMOUNT = 5.5;

    /** @var Gateway|\PHPUnit\Framework\MockObject\MockObject */
    protected $gateway;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var PayPalExpressCheckoutPaymentMethod */
    protected $expressCheckout;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var PayPalExpressCheckoutConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentConfig;

    /** @var OptionsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $optionsProvider;

    /** @var SurchargeProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $surchargeProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->paymentConfig = $this->createMock(PayPalExpressCheckoutConfigInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->optionsProvider = $this->createMock(OptionsProviderInterface::class);
        $this->surchargeProvider = $this->createMock(SurchargeProvider::class);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->expressCheckout = new PayPalExpressCheckoutPaymentMethod(
            $this->gateway,
            $this->paymentConfig,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProvider,
            $this->surchargeProvider,
            $this->propertyAccessor
        );
    }

    protected function tearDown(): void
    {
        unset($this->paymentConfig, $this->router, $this->gateway, $this->doctrineHelper, $this->expressCheckout);
    }

    public function testExecute()
    {
        $transaction = $this->createTransaction(PaymentMethodInterface::CHARGE);

        $this->gateway->expects($this->any())
            ->method('request')
            ->with(
                'S',
                array_merge(
                    ['ACTION' => 'S'],
                    $this->getAdditionalOptions()
                )
            )
            ->willReturn(new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'TOKEN' => 'TOKEN']));

        $this->gateway->expects($this->exactly(1))
            ->method('setTestMode')
            ->with(false);

        $this->expressCheckout->execute($transaction->getAction(), $transaction);
        $this->assertTrue($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());
    }

    public function testExecuteWithoutPNREF()
    {
        $transaction = $this->createTransaction(PaymentMethodInterface::CHARGE);

        $this->gateway->expects($this->any())
            ->method('request')
            ->with(
                'S',
                array_merge(
                    ['ACTION' => 'S'],
                    $this->getAdditionalOptions()
                )
            )
            ->willReturn(new Response(['RESPMSG' => 'Error', 'RESULT' => '1']));

        $this->gateway->expects($this->exactly(1))
            ->method('setTestMode')
            ->with(false);

        $this->expressCheckout->execute($transaction->getAction(), $transaction);
        $this->assertFalse($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());
    }

    public function testExecuteException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported action "wrong_action"');

        $transaction = $this->createTransaction('wrong_action');
        $this->expressCheckout->execute($transaction->getAction(), $transaction);
    }

    public function testGetIdentifier()
    {
        $this->paymentConfig->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('payflow_express_checkout');
        $this->assertSame('payflow_express_checkout', $this->expressCheckout->getIdentifier());
    }

    /**
     * @param float $amount
     * @param bool $expectedIsApplicable
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable($amount, $expectedIsApplicable)
    {
        /** @var PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects($this->once())
            ->method('getTotal')
            ->willReturn($amount);

        $this->assertEquals($expectedIsApplicable, $this->expressCheckout->isApplicable($context));
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider()
    {
        return [
            'not applicable if order total is zero' => [
                'amount' => 0.0,
                'expectedIsApplicable' => false
            ],
            'applicable if order total is greater than zero' => [
                'amount' => 0.1,
                'expectedIsApplicable' => true
            ]
        ];
    }

    /**
     * @param bool $expected
     * @param string $actionName
     * @dataProvider supportsDataProvider
     */
    public function testSupports($expected, $actionName)
    {
        $this->assertSame($expected, $this->expressCheckout->supports($actionName));
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
            [true, PayPalExpressCheckoutPaymentMethod::COMPLETE],
            [false, PaymentMethodInterface::VALIDATE],
        ];
    }

    public function testPurchaseGetActionFromConfig()
    {
        $this->configCredentials();
        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $transaction = $this->createTransaction(PaymentMethodInterface::PURCHASE);

        $this->surchargeProvider->expects($this->never())
            ->method('getSurcharges');

        $this->gateway->expects($this->once())
            ->method('request')
            ->with('A')
            ->willReturn(
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'TOKEN' => self::TOKEN])
            );

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with(false);

        $result = $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->assertSame(PaymentMethodInterface::AUTHORIZE, $transaction->getAction());
        $this->assertArrayHasKey('purchaseRedirectUrl', $result);
        $this->assertSame(sprintf(self::PRODUCTION_REDIRECT_URL, self::TOKEN), $result['purchaseRedirectUrl']);
    }

    public function testPurchasePaymentTransactionNonActive()
    {
        $this->configCredentials();
        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $transaction = $this->createTransaction(PaymentMethodInterface::PURCHASE);

        $this->surchargeProvider->expects($this->never())
            ->method('getSurcharges');

        $this->gateway->expects($this->once())
            ->method('request')
            ->with('A')
            ->willReturn(
                new Response(['RESPMSG' => 'NonApproved', 'RESULT' => '12', 'TOKEN' => self::TOKEN])
            );

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with(false);

        $result = $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->assertEmpty($result);
        $this->assertArrayNotHasKey('purchaseRedirectUrl', $result);
    }

    public function testPurchaseCheckRequest()
    {
        $this->configCredentials();

        $requestData = array_merge(
            $this->getCredentials(),
            $this->getAdditionalOptions(),
            $this->getExpressCheckoutOptions(),
            $this->getShippingAddressOptions(),
            $this->getLineItemOptions(),
            $this->getSurchargeOptions()
        );

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $transaction = $this->createTransaction(PaymentMethodInterface::PURCHASE);

        $entity = $this->getEntity();

        $surcharge = new Surcharge();
        $surcharge->setShippingAmount(self::SHIPPING_COST);
        $surcharge->setDiscountAmount(self::DISCOUNT_AMOUNT);

        $this->surchargeProvider->expects($this->once())
            ->method('getSurcharges')
            ->with($entity)
            ->willReturn($surcharge);

        $this->router
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                [
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ],
                [
                    'oro_payment_callback_error',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls('callbackReturnUrl', 'callbackErrorUrl');

        $this->gateway->expects($this->once())
            ->method('request')
            ->with('A', $requestData)
            ->willReturn(new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'TOKEN' => self::TOKEN]));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->with(self::ENTITY_CLASS, self::ENTITY_ID)
            ->willReturn($entity);

        $this->configureLineItemOptions();
        $this->configureShippingAddressOptions();

        $result = $this->expressCheckout->execute($transaction->getAction(), $transaction);
        $this->assertSame(PaymentMethodInterface::AUTHORIZE, $transaction->getAction());
        $this->assertArrayHasKey('purchaseRedirectUrl', $result);
    }

    protected function configureShippingAddressOptions()
    {
        $entity = $this->getEntity();

        $this->optionsProvider
            ->expects($this->once())
            ->method('getShippingAddressOptions')
            ->with($entity->getShippingAddress())
            ->willReturn($this->getAddressOptionModel());
    }

    protected function configureLineItemOptions()
    {
        $entity = $this->getEntity();

        $this->optionsProvider
            ->expects($this->once())
            ->method('getLineItemOptions')
            ->with($entity)
            ->willReturn([$this->getLineItemOptionModel()]);
    }

    public function testPurchaseWithoutShippingAddress()
    {
        $this->configCredentials();

        $requestData = array_merge(
            $this->getCredentials(),
            $this->getAdditionalOptions(),
            $this->getExpressCheckoutOptions(),
            $this->getLineItemOptions(),
            $this->getSurchargeOptions()
        );

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $transaction = $this->createTransaction(PaymentMethodInterface::PURCHASE);

        $surcharge = new Surcharge();
        $surcharge->setShippingAmount(self::SHIPPING_COST);
        $surcharge->setDiscountAmount(self::DISCOUNT_AMOUNT);
        $this->surchargeProvider->expects($this->once())
            ->method('getSurcharges')
            ->willReturn($surcharge);

        $this->router
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                [
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ],
                [
                    'oro_payment_callback_error',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls('callbackReturnUrl', 'callbackErrorUrl');

        $this->gateway->expects($this->once())
            ->method('request')
            ->with('A', $requestData)
            ->willReturn(new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'TOKEN' => self::TOKEN]));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->with(self::ENTITY_CLASS, self::ENTITY_ID)
            ->willReturnOnConsecutiveCalls($this->getEntity(), new \stdClass());

        $this->configureLineItemOptions();
        $this->expressCheckout->execute($transaction->getAction(), $transaction);
    }

    /**
     * @dataProvider purchaseDataProvider
     * @param bool $testMode
     * @param string $redirectUrl
     */
    public function testPurchaseCheckRedirectUrl($testMode, $redirectUrl)
    {
        $this->configCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $this->paymentConfig->expects($this->any())
            ->method('isTestMode')
            ->willReturn($testMode);

        $transaction = $this->createTransaction(PaymentMethodInterface::PURCHASE);

        $this->surchargeProvider->expects($this->never())
            ->method('getSurcharges');

        $this->gateway->expects($this->once())
            ->method('request')
            ->with('A')
            ->willReturn(new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'TOKEN' => self::TOKEN]));

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with($testMode);

        $result = $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->assertArrayHasKey('purchaseRedirectUrl', $result);
        $this->assertSame($redirectUrl, $result['purchaseRedirectUrl']);
    }

    /**
     * @return array
     */
    public function purchaseDataProvider()
    {
        return [
            'production mode' => [
                'testMode' => false,
                'redirectUrl' => sprintf(self::PRODUCTION_REDIRECT_URL, self::TOKEN),
            ],
            'test mode' => [
                'testMode' => true,
                'redirectUrl' => sprintf(self::PILOT_REDIRECT_URL, self::TOKEN),
            ],
        ];
    }

    /**
     * @dataProvider getLineItemOptionsProvider
     * @param object $entity
     * @param array $requestData
     * @param int $calls
     */
    public function testGetLineItemOptions($entity, array $requestData, $calls)
    {
        $this->configCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $transaction = $this->createTransaction(PaymentMethodInterface::PURCHASE);

        $surcharge = new Surcharge();
        $surcharge->setShippingAmount(self::SHIPPING_COST);
        $surcharge->setDiscountAmount(self::DISCOUNT_AMOUNT);
        $this->surchargeProvider->expects($this->once())
            ->method('getSurcharges')
            ->with($entity)
            ->willReturn($surcharge);

        $this->gateway->expects($this->once())
            ->method('request')
            ->with('A', $requestData)
            ->willReturn(
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0'])
            );

        $this->router
            ->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                [
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ],
                [
                    'oro_payment_callback_error',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls('callbackReturnUrl', 'callbackErrorUrl');

        $entityStub = $this->getEntity();
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityReference')
            ->willReturnOnConsecutiveCalls($entity, $entity, $entityStub);

        $this->optionsProvider
            ->expects($this->exactly($calls))
            ->method('getLineItemOptions')
            ->willReturn([]);

        $this->configureShippingAddressOptions();
        $this->expressCheckout->execute($transaction->getAction(), $transaction);
    }

    /**
     * @return array
     */
    public function getLineItemOptionsProvider()
    {
        return [
            'non LineItemsAwareInterface' => [
                'entity' => new \stdClass(),
                'requestData' => array_merge(
                    $this->getCredentials(),
                    $this->getAdditionalOptions(),
                    $this->getExpressCheckoutOptions(),
                    $this->getShippingAddressOptions(),
                    $this->getSurchargeOptions()
                ),
                'getLineItemPaymentOptions calls' => 0

            ],
            'lineItem without product' => [
                'entity' => $this->getEntity(),
                'requestData' => array_merge(
                    $this->getCredentials(),
                    $this->getAdditionalOptions(),
                    $this->getExpressCheckoutOptions(),
                    $this->getShippingAddressOptions(),
                    $this->getSurchargeOptions(),
                    ['ITEMAMT' => 0, 'TAXAMT' => 0]
                ),
                'getLineItemPaymentOptions calls' => 1
            ],
        ];
    }

    public function testCompleteSuccess()
    {
        $this->configCredentials();

        // Prepare authorize transaction to use it then for complete action
        $transaction = $this->createTransaction(PaymentMethodInterface::AUTHORIZE);

        $authorizeTransactionRequest = array_merge(['ACTION' => 'S'], $this->getAdditionalOptions());
        $this->gateway->expects($this->at(1))
            ->method('request')
            ->with('A', $authorizeTransactionRequest)
            ->willReturn(
                new Response(
                    [
                        'RESPMSG' => 'Approved',
                        'RESULT' => '0',
                        'TOKEN' => self::TOKEN,
                        'PayerID' => 'payerIdTest',
                    ]
                )
            );

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with(false);

        $this->expressCheckout->execute($transaction->getAction(), $transaction);

        // Start complete logic

        $this->configureLineItemOptions();
        $entity = $this->getEntity();

        $surcharge = new Surcharge();
        $surcharge->setShippingAmount(self::SHIPPING_COST);
        $surcharge->setDiscountAmount(self::DISCOUNT_AMOUNT);

        $this->surchargeProvider->expects($this->once())
            ->method('getSurcharges')
            ->with($entity)
            ->willReturn($surcharge);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->with(self::ENTITY_CLASS, self::ENTITY_ID)
            ->willReturn($entity);

        $completeTransactionRequest = [
            'AMT' => '10',
            'TOKEN' => self::TOKEN,
            'ACTION' => 'D',
            'PAYERID' => 'payerIdTest',
        ];

        $transaction->setAction(PayPalExpressCheckoutPaymentMethod::COMPLETE);

        $completeTransactionRequest = array_merge(
            $completeTransactionRequest,
            $this->getCredentials(),
            $this->getAdditionalOptions(),
            $this->getLineItemOptions(),
            $this->getSurchargeOptions()
        );

        $this->gateway->expects($this->at(1))
            ->method('request')
            ->with('A', $completeTransactionRequest)
            ->willReturn(
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0'])
            );

        $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
    }

    public function testCompleteWithPendingReason()
    {
        $this->configCredentials();

        $transaction = $this->createTransaction(PaymentMethodInterface::AUTHORIZE);

        $this->gateway->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(['A'], ['A'])
            ->willReturnOnConsecutiveCalls(
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'TOKEN' => self::TOKEN]),
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'PENDINGREASON' => 'echeck'])
            );

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with(false);

        $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->expressCheckout->execute(PayPalExpressCheckoutPaymentMethod::COMPLETE, $transaction);

        $this->assertTrue($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());
    }

    public function testCompleteWithAuthorizeAction()
    {
        $this->configCredentials();

        $transaction = $this->createTransaction(PaymentMethodInterface::AUTHORIZE);

        $this->gateway->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(['A'], ['A'])
            ->willReturnOnConsecutiveCalls(
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'TOKEN' => self::TOKEN]),
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0'])
            );

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with(false);

        $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->expressCheckout->execute(PayPalExpressCheckoutPaymentMethod::COMPLETE, $transaction);

        $this->assertTrue($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());
    }

    public function testComplete()
    {
        $this->configCredentials();

        $requestOptions = array_merge(
            $this->getCredentials(),
            $this->getAdditionalOptions(),
            [
                'AMT' => 10,
                'TOKEN' => self::TOKEN,
                'ACTION' => 'D',
            ]
        );

        $transaction = $this->createTransaction(PayPalExpressCheckoutPaymentMethod::COMPLETE);
        $transaction->setReference(self::TOKEN);

        $this->gateway->expects($this->once())
            ->method('request')
            ->with(null, $requestOptions)
            ->willReturn(
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'TOKEN' => self::TOKEN])
            );

        $this->expressCheckout->execute(PayPalExpressCheckoutPaymentMethod::COMPLETE, $transaction);
    }

    public function testCaptureWithoutSourcePaymentTransaction()
    {
        $transaction = $this->createTransaction(PaymentMethodInterface::CAPTURE);

        $this->gateway->expects($this->never())
            ->method('request');

        $result = $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->assertFalse($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());
        $this->assertArrayHasKey('successful', $result);
        $this->assertFalse($result['successful']);
    }

    public function testCaptureSuccess()
    {
        $this->configCredentials();

        $transaction = $this->createTransaction(PaymentMethodInterface::CAPTURE);
        $sourceTransaction = new PaymentTransaction();
        $sourceTransaction->setReference('referenceId');

        $transaction->setSourcePaymentTransaction($sourceTransaction);

        $requestOptions = array_merge(
            $this->getCredentials(),
            $this->getAdditionalOptions(),
            $this->getDelayedCaptureOptions()
        );

        $this->gateway->expects($this->once())
            ->method('request')
            ->with('D', $requestOptions)
            ->willReturn(
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0'])
            );

        $result = $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
        $this->assertFalse($sourceTransaction->isActive());

        $this->assertArrayHasKey('message', $result);
        $this->assertSame('Approved', $result['message']);
        $this->assertArrayHasKey('successful', $result);
        $this->assertTrue($result['successful']);
    }

    /**
     * @dataProvider responseWithErrorDataProvider
     *
     * @param string $responseMessage
     * @param string $expectedMessage
     */
    public function testCaptureError($responseMessage, $expectedMessage)
    {
        $this->configCredentials();

        $transaction = $this->createTransaction(PaymentMethodInterface::CAPTURE);
        $sourceTransaction = new PaymentTransaction();
        $sourceTransaction->setReference('referenceId');

        $transaction->setSourcePaymentTransaction($sourceTransaction);

        $requestOptions = array_merge(
            $this->getCredentials(),
            $this->getAdditionalOptions(),
            $this->getDelayedCaptureOptions()
        );

        $this->gateway
            ->expects($this->once())
            ->method('request')
            ->with('D', $requestOptions)
            ->willReturn(
                new Response(['RESULT' => '-1', 'RESPMSG' => $responseMessage])
            );

        $result = $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
        $this->assertTrue($sourceTransaction->isActive());

        $this->assertArrayHasKey('message', $result);
        $this->assertSame($expectedMessage, $result['message']);
        $this->assertArrayHasKey('successful', $result);
        $this->assertFalse($result['successful']);
    }

    /**
     * @return array
     */
    public function responseWithErrorDataProvider()
    {
        return [
            'RESPMSG is filled' => [
                'responseMessage' => 'Error message',
                'expectedMessage' => 'Error message',
            ],
            'RESPMSG is not filled, message is translated from response code' => [
                'responseMessage' => '',
                'expectedMessage' => 'Failed to connect to host',
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getCredentials()
    {
        return [
            'VENDOR' => null,
            'USER' => null,
            'PWD' => null,
            'PARTNER' => 'PayPal',
            'TENDER' => 'P',
        ];
    }

    /**
     * @param string $action
     * @return PaymentTransaction
     */
    protected function createTransaction($action)
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setCurrency('USD')
            ->setAmount('10')
            ->setAction($action)
            ->setAccessIdentifier('testAccessIdentifier')
            ->setEntityClass(self::ENTITY_CLASS)
            ->setEntityIdentifier(self::ENTITY_ID);

        return $paymentTransaction;
    }

    /**
     * @return array
     */
    protected function getExpressCheckoutOptions()
    {
        return [
            'PAYMENTTYPE' => 'instantonly',
            'ADDROVERRIDE' => 1,
            'AMT' => '10',
            'CURRENCY' => 'USD',
            'RETURNURL' => 'callbackReturnUrl',
            'CANCELURL' => 'callbackErrorUrl',
            'ACTION' => 'S',
        ];
    }

    /**
     * @return array
     */
    protected function getShippingAddressOptions()
    {
        return [
            'SHIPTOFIRSTNAME' => 'First Name',
            'SHIPTOLASTNAME' => 'Last Name',
            'SHIPTOSTREET' => 'Street',
            'SHIPTOSTREET2' => 'Street2',
            'SHIPTOCITY' => 'City',
            'SHIPTOSTATE' => 'State',
            'SHIPTOZIP' => 'Zip Code',
            'SHIPTOCOUNTRY' => 'US',
        ];
    }

    /**
     * @return array
     */
    protected function getLineItemOptions()
    {
        return [
            'L_NAME1' => 'Product Name',
            'L_DESC1' => 'Product Description',
            'L_COST1' => 55.4,
            'L_QTY1' => 15,
            'L_TAXAMT1' => 0,
            'ITEMAMT' => 831,
            'TAXAMT' => 0
        ];
    }

    /**
     * @return array
     */
    protected function getSurchargeOptions()
    {
        return [
            'FREIGHTAMT' => self::SHIPPING_COST,
            'HANDLINGAMT' => 0,
            'DISCOUNT' => -self::DISCOUNT_AMOUNT,
            'INSURANCEAMT' => 0,
        ];
    }

    /**
     * @return LineItemOptionModel
     */
    protected function getLineItemOptionModel()
    {
        $lineItemModel = new LineItemOptionModel();

        return $lineItemModel
            ->setName('Product Name')
            ->setDescription('Product Description')
            ->setCost(55.4)
            ->setQty(15);
    }

    /**
     * @return AddressOptionModel
     */
    protected function getAddressOptionModel()
    {
        $addressOptionModel = new AddressOptionModel();

        return $addressOptionModel
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setStreet('Street')
            ->setStreet2('Street2')
            ->setCity('City')
            ->setRegionCode('State')
            ->setPostalCode('Zip Code')
            ->setCountryIso2('US');
    }

    /**
     * @return array
     */
    protected function getDelayedCaptureOptions()
    {
        return [
            'AMT' => '10',
            'ORIGID' => 'referenceId',
        ];
    }

    /**
     * @return EntityStub
     */
    protected function getEntity()
    {
        /** @var AbstractAddress|\PHPUnit\Framework\MockObject\MockObject $abstractAddressMock */
        $abstractAddressMock = $this->getMockForAbstractClass(AbstractAddress::class);

        return new EntityStub($abstractAddressMock);
    }

    protected function configCredentials()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getCredentials')
            ->willReturn($this->getCredentials());
    }

    /**
     * @return array
     */
    protected function getAdditionalOptions()
    {
        return [
            'BUTTONSOURCE' => 'OroCommerce_SP'
        ];
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\PaymentBundle\Model\AddressOptionModel;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Provider\ExtractOptionsProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class PayflowExpressCheckoutTest extends \PHPUnit_Framework_TestCase
{
    const CONFIG_PREFIX = 'payflow_express_checkout_';
    const PRODUCTION_REDIRECT_URL = 'https://www.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';
    const PILOT_REDIRECT_URL = 'https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&useraction=commit&token=%s';
    const TOKEN = 'token';
    const ENTITY_CLASS = 'EntityClass';
    const ENTITY_ID = 15689;

    /** @var Gateway|\PHPUnit_Framework_MockObject_MockObject */
    protected $gateway;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var PayflowExpressCheckout */
    protected $expressCheckout;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var PayflowExpressCheckoutConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentConfig;

    /** @var ExtractOptionsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $optionsProviderMock;

    protected function setUp()
    {
        $this->gateway = $this->getMockBuilder(Gateway::class)->disableOriginalConstructor()->getMock();
        $this->router = $this->getMock(RouterInterface::class);
        $this->paymentConfig = $this->getMock(PayflowExpressCheckoutConfigInterface::class);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();

        $this->optionsProviderMock = $this->getMockBuilder(ExtractOptionsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expressCheckout = new PayflowExpressCheckout(
            $this->gateway,
            $this->paymentConfig,
            $this->router,
            $this->doctrineHelper,
            $this->optionsProviderMock
        );
    }

    protected function tearDown()
    {
        unset($this->paymentConfig, $this->router, $this->gateway, $this->doctrineHelper, $this->expressCheckout);
    }

    public function testExecute()
    {
        $transaction = $this->createTransaction(PaymentMethodInterface::CHARGE);

        $this->gateway->expects($this->any())
            ->method('request')
            ->with('S', ['ACTION' => 'S'])
            ->willReturn(new Response(['RESPMSG' => 'Approved', 'RESULT' => '0']));

        $this->gateway->expects($this->exactly(1))
            ->method('setTestMode')
            ->with(false);

        $this->expressCheckout->execute($transaction->getAction(), $transaction);
        $this->assertTrue($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported action "wrong_action"
     */
    public function testExecuteException()
    {
        $transaction = $this->createTransaction('wrong_action');
        $this->expressCheckout->execute($transaction->getAction(), $transaction);
    }

    public function testGetType()
    {
        $this->assertSame('payflow_express_checkout', $this->expressCheckout->getType());
    }

    public function testIsEnabled()
    {
        $this->paymentConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->assertTrue($this->expressCheckout->isEnabled());
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->expressCheckout->isApplicable());
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
            [true, PayflowExpressCheckout::COMPLETE],
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
            $this->getExpressCheckoutOptions(),
            $this->getShippingAddressOptions(),
            $this->getLineItemOptions()
        );

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $transaction = $this->createTransaction(PaymentMethodInterface::PURCHASE);

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

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityReference')
            ->with(self::ENTITY_CLASS, self::ENTITY_ID)
            ->willReturn($this->getEntity());

        $this->configureLineItemOptions();
        $this->configureShippingAddressOptions();

        $result = $this->expressCheckout->execute($transaction->getAction(), $transaction);
        $this->assertSame(PaymentMethodInterface::AUTHORIZE, $transaction->getAction());
        $this->assertArrayHasKey('purchaseRedirectUrl', $result);
    }

    protected function configureShippingAddressOptions()
    {
        $entity = $this->getEntity();

        $this->doctrineHelper->expects($this->once())->method('getEntityClass')->with($entity->getShippingAddress())
            ->willReturn(AbstractAddress::class);

        $this->optionsProviderMock->expects($this->once())->method('getShippingAddressOptions')
            ->with(AbstractAddress::class, $entity->getShippingAddress())
            ->willReturn($this->getAddressOptionModel());
    }

    protected function configureLineItemOptions()
    {
        $entity = $this->getEntity();

        $this->optionsProviderMock
            ->expects($this->once())
            ->method('getLineItemPaymentOptions')
            ->with($entity)
            ->willReturn([$this->getLineItemOptionModel()]);
    }

    public function testPurchaseWithoutShippingAddress()
    {
        $this->configCredentials();

        $requestData = array_merge(
            $this->getCredentials(),
            $this->getExpressCheckoutOptions(),
            $this->getLineItemOptions()
        );

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $transaction = $this->createTransaction(PaymentMethodInterface::PURCHASE);

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

        $this->doctrineHelper->expects($this->exactly(2))
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
            ->expects($this->exactly(2))
            ->method('getEntityReference')
            ->willReturnOnConsecutiveCalls($entity, $entityStub);

        $this->optionsProviderMock
            ->expects($this->exactly($calls))
            ->method('getLineItemPaymentOptions')
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
                    $this->getExpressCheckoutOptions(),
                    $this->getShippingAddressOptions()
                ),
                'getLineItemPaymentOptions calls' => 0

            ],
            'lineItem without product' => [
                'entity' => $this->getEntity(),
                'requestData' => array_merge(
                    $this->getCredentials(),
                    $this->getExpressCheckoutOptions(),
                    $this->getShippingAddressOptions(),
                    ['ITEMAMT' => 0]
                ),
                'getLineItemPaymentOptions calls' => 1
            ],
        ];
    }

    public function testCompleteSuccess()
    {
        $this->configCredentials();

        $transactionRequest = [
            'AMT' => '10',
            'TOKEN' => self::TOKEN,
            'ACTION' => 'D',
            'PAYERID' => 'payerIdTest',
        ];

        $transactionRequest = array_merge($transactionRequest, $this->getCredentials());

        $transaction = $this->createTransaction(PaymentMethodInterface::AUTHORIZE);

        $this->gateway->expects($this->exactly(2))
            ->method('request')
            ->withConsecutive(['A'], ['A', $transactionRequest])
            ->willReturnOnConsecutiveCalls(
                new Response(
                    [
                        'RESPMSG' => 'Approved',
                        'RESULT' => '0',
                        'TOKEN' => self::TOKEN,
                        'PayerID' => 'payerIdTest',
                    ]
                ),
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0'])
            );

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with(false);

        $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $transaction->setAction(PayflowExpressCheckout::COMPLETE);

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

        $this->expressCheckout->execute(PayflowExpressCheckout::COMPLETE, $transaction);

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

        $this->expressCheckout->execute(PayflowExpressCheckout::COMPLETE, $transaction);

        $this->assertTrue($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());
    }

    public function testComplete()
    {
        $this->configCredentials();

        $requestOptions = array_merge(
            $this->getCredentials(),
            [
                'AMT' => 10,
                'TOKEN' => self::TOKEN,
                'ACTION' => 'D',
            ]
        );

        $transaction = $this->createTransaction(PayflowExpressCheckout::COMPLETE);
        $transaction->setReference(self::TOKEN);

        $this->gateway->expects($this->once())
            ->method('request')
            ->with(null, $requestOptions)
            ->willReturn(
                new Response(['RESPMSG' => 'Approved', 'RESULT' => '0', 'TOKEN' => self::TOKEN])
            );

        $this->expressCheckout->execute(PayflowExpressCheckout::COMPLETE, $transaction);
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
            'ADDROVERRIDE' => true,
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
            'ITEMAMT' => 831
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
        /** @var AbstractAddress|\PHPUnit_Framework_MockObject_MockObject $abstractAddressMock */
        $abstractAddressMock = $this->getMockBuilder(AbstractAddress::class)->getMock();

        return new EntityStub($abstractAddressMock);
    }

    protected function configCredentials()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getCredentials')
            ->willReturn($this->getCredentials());
    }
}

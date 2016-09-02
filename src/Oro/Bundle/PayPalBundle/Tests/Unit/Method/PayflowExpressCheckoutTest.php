<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

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

    protected function setUp()
    {
        $this->gateway = $this->getMockBuilder('Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway')
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentConfig =
            $this->getMock('Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface');

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->expressCheckout = new PayflowExpressCheckout(
            $this->gateway,
            $this->paymentConfig,
            $this->router,
            $this->doctrineHelper
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
     *
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
                    'orob2b_payment_callback_return',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ],
                [
                    'orob2b_payment_callback_error',
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
            ->willReturn($this->createOrderWithShippingAddress());

        $result = $this->expressCheckout->execute($transaction->getAction(), $transaction);

        $this->assertSame(PaymentMethodInterface::AUTHORIZE, $transaction->getAction());
        $this->assertArrayHasKey('purchaseRedirectUrl', $result);
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
                    'orob2b_payment_callback_return',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ],
                [
                    'orob2b_payment_callback_error',
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
            ->willReturnOnConsecutiveCalls($this->createOrderWithShippingAddress(), new \stdClass());

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
     */
    public function testGetLineItemOptions($entity, array $requestData)
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
                    'orob2b_payment_callback_return',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ],
                [
                    'orob2b_payment_callback_error',
                    [
                        'accessIdentifier' => 'testAccessIdentifier',
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls('callbackReturnUrl', 'callbackErrorUrl');

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityReference')
            ->willReturnOnConsecutiveCalls($entity, $this->createOrderWithShippingAddress());

        $this->expressCheckout->execute($transaction->getAction(), $transaction);
    }

    /**
     * @return array
     */
    public function getLineItemOptionsProvider()
    {
        $entityWithoutProduct = new Order();
        $entityWithoutProduct->addLineItem(new OrderLineItem());

        return [
            'non LineItemsAwareInterface' => [
                'entity' => new \stdClass(),
                'requestData' => array_merge(
                    $this->getCredentials(),
                    $this->getExpressCheckoutOptions(),
                    $this->getShippingAddressOptions()
                ),
            ],
            'lineItem without product' => [
                'entity' => $entityWithoutProduct,
                'requestData' => array_merge(
                    $this->getCredentials(),
                    $this->getExpressCheckoutOptions(),
                    $this->getShippingAddressOptions(),
                    ['ITEMAMT' => 0]
                ),
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
     * @param array $configs
     */
    protected function configureConfig(array $configs = [])
    {
        $map = [];
        array_walk(
            $configs,
            function ($val, $key) use (&$map) {
                $map[] = [$this->getConfigKey($key), false, false, $val];
            }
        );

        $this->paymentConfig->expects($this->exactly(3))
            ->method('get')
            ->will($this->returnValueMap($map));
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
            'ITEMAMT' => 831,
        ];
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
     * @return Order
     */
    protected function createOrderWithShippingAddress()
    {
        $order = new Order();

        $order->setShippingAddress($this->createOrderAddress());
        $order->addLineItem($this->createOrderLineItem());

        return $order;
    }

    /**
     * @return OrderAddress
     */
    protected function createOrderAddress()
    {
        $region = new Region('US-NY');
        $region->setCode('State');

        $country = new Country('US');
        $address = new OrderAddress();

        $address->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setStreet('Street')
            ->setStreet2('Street2')
            ->setCity('City')
            ->setRegion($region)
            ->setPostalCode('Zip Code')
            ->setCountry($country);

        return $address;

    }

    /**
     * @return OrderLineItem
     */
    protected function createOrderLineItem()
    {
        $lineItem = new OrderLineItem();
        $product = new Product();
        $product
            ->addName((new LocalizedFallbackValue())->setString('Product Name'))
            ->addShortDescription((new LocalizedFallbackValue())->setText('Product Description'));

        $lineItem
            ->setProduct($product)
            ->setValue(55.4)
            ->setQuantity(15);

        return $lineItem;
    }

    protected function configCredentials()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getCredentials')
            ->willReturn($this->getCredentials());
    }
}

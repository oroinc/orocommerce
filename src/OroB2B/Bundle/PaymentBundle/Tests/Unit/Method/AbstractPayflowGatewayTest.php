<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use Symfony\Component\Routing\RouterInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseStatusMap;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\Method\PayPalPaymentsPro;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractPayflowGatewayTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait, EntityTrait;

    /** @var Gateway|\PHPUnit_Framework_MockObject_MockObject */
    protected $gateway;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var PayflowGateway|PayPalPaymentsPro */
    protected $method;

    protected function setUp()
    {
        $this->gateway = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Gateway')
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->method = $this->getMethod();
    }

    /**
     * @return PayflowGateway|PayPalPaymentsPro
     */
    abstract protected function getMethod();

    protected function tearDown()
    {
        unset($this->configManager, $this->router, $this->gateway);
    }

    public function testExecute()
    {
        $sourcePaymentTransaction = new PaymentTransaction();
        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::CHARGE);
        $transaction->setSourcePaymentTransaction($sourcePaymentTransaction);

        $this->gateway->expects($this->any())
            ->method('request')
            ->with('S')
            ->willReturn(new Response(['PNREF' => 'reference', 'RESULT' => '0']));

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with(false);

        $this->assertEquals(
            [
                'message' => null,
                'successful' => true,
            ],
            $this->method->execute($transaction)
        );

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unsupported action "wrong_action"
     */
    public function testExecuteException()
    {
        $transaction = new PaymentTransaction();
        $transaction->setAction('wrong_action');

        $this->method->execute($transaction);
    }

    public function testIsEnabled()
    {
        $this->setConfig($this->once(), $this->getConfigPrefix() . 'enabled', true);
        $this->assertTrue($this->method->isEnabled());
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
        ];
    }

    /**
     * @param bool $expected
     * @param bool $configValue
     *
     * @dataProvider validateSupportsDataProvider
     */
    public function testSupportsValidate($expected, $configValue)
    {
        $this->configureConfig([$this->getConfigPrefix() . 'zero_amount_authorization' => $configValue]);

        $this->assertEquals($expected, $this->method->supports(PaymentMethodInterface::VALIDATE));
    }

    /**
     * @return array
     */
    public function validateSupportsDataProvider()
    {
        return [
            [true, true],
            [false, false],
        ];
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

        $this->configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));
    }

    public function testAuthorizeDoNotPerformRequestIfAmountAuthorizationIsNotRequiredAndValidationExists()
    {
        $this->configureConfig(
            [$this->getConfigPrefix() . 'authorization_for_required_amount' => false]
        );

        $sourceTransaction = new PaymentTransaction();
        $sourceTransaction
            ->setActive(false)
            ->setSuccessful(true)
            ->setCurrency('USD')
            ->setReference('source_reference');
        $transaction = new PaymentTransaction();
        $transaction
            ->setReference('reference')
            ->setRequest(['not empty'])
            ->setSourcePaymentTransaction($sourceTransaction);

        $this->gateway->expects($this->never())->method($this->anything());

        $this->method->authorize($transaction);

        $this->assertEquals($sourceTransaction->getReference(), $transaction->getReference());
        $this->assertEquals($sourceTransaction->getCurrency(), $transaction->getCurrency());
        $this->assertEquals($sourceTransaction->isSuccessful(), $transaction->isSuccessful());
        $this->assertEquals($sourceTransaction->isActive(), $transaction->isActive());
        $this->assertEmpty($transaction->getRequest());
        $this->assertEmpty($transaction->getResponse());
    }

    public function testAuthorizePerformRequestIfAmountAuthorizationIsNotRequiredAndValidationIsMissing()
    {
        $this->configureConfig(
            [$this->getConfigPrefix() . 'authorization_for_required_amount' => false]
        );

        $transaction = new PaymentTransaction();

        $this->gateway->expects($this->once())->method('request')->with('A')
            ->willReturn(new Response(['PNREF' => 'reference', 'RESULT' => '0']));

        $this->method->authorize($transaction);

        $this->assertEquals('reference', $transaction->getReference());
    }

    public function testAuthorizePerformRequest()
    {
        $this->configureConfig(
            [$this->getConfigPrefix() . 'authorization_for_required_amount' => true]
        );

        $transaction = new PaymentTransaction();

        $this->gateway->expects($this->once())->method('request')->with('A')
            ->willReturn(new Response(['PNREF' => 'reference', 'RESULT' => '0']));

        $this->method->authorize($transaction);

        $this->assertEquals('reference', $transaction->getReference());
    }

    public function testChargeWithoutSourceTransaction()
    {
        $transaction = new PaymentTransaction();

        $this->gateway->expects($this->once())->method('request')->with('S')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $response = $this->method->charge($transaction);

        $this->assertEquals('reference', $transaction->getReference());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertTrue($transaction->isActive());
        $this->assertArrayHasKey('successful', $response);
        $this->assertTrue($response['successful']);
    }

    public function testChargeDeactivateSourceTransactionIfItsNotValidationOne()
    {
        $sourceTransaction = new PaymentTransaction();
        $sourceTransaction
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setSuccessful(true)
            ->setActive(true);

        $transaction = new PaymentTransaction();
        $transaction
            ->setSourcePaymentTransaction($sourceTransaction)
            ->setSuccessful(true)
            ->setActive(true);

        $this->gateway->expects($this->once())->method('request')->with('S')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->charge($transaction);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
        $this->assertTrue($sourceTransaction->isSuccessful());
        $this->assertFalse($sourceTransaction->isActive());
    }

    public function testChargeDoNotChangeValidateSourceTransactionState()
    {
        $sourceTransaction = new PaymentTransaction();
        $sourceTransaction
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setSuccessful(true)
            ->setActive(true);

        $transaction = new PaymentTransaction();
        $transaction
            ->setSourcePaymentTransaction($sourceTransaction)
            ->setSuccessful(true)
            ->setActive(true);

        $this->gateway->expects($this->once())->method('request')->with('S')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->charge($transaction);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
        $this->assertTrue($sourceTransaction->isSuccessful());
        $this->assertTrue($sourceTransaction->isActive());
    }

    public function testCaptureDoChargeIfSourceAuthorizationIsValidationTransactionClone()
    {
        $sourceTransaction = new PaymentTransaction();
        $sourceTransaction
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setSourcePaymentTransaction(
                (new PaymentTransaction())->setAction(PaymentMethodInterface::VALIDATE)->setReference('VALIDATE')
            )
            ->setReference('VALIDATE')
            ->setSuccessful(true)
            ->setActive(true);

        $transaction = new PaymentTransaction();
        $transaction
            ->setSourcePaymentTransaction($sourceTransaction)
            ->setSuccessful(true)
            ->setActive(true);

        $this->gateway->expects($this->once())->method('request')->with('S')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->capture($transaction);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
        $this->assertTrue($sourceTransaction->isSuccessful());
        $this->assertFalse($sourceTransaction->isActive());
    }

    public function testCaptureWithoutSourceTransaction()
    {
        $transaction = new PaymentTransaction();
        $transaction
            ->setSuccessful(true)
            ->setActive(true);

        $this->gateway->expects($this->never())->method($this->anything());

        $this->method->capture($transaction);

        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
    }

    public function testCapture()
    {
        $sourceTransaction = new PaymentTransaction();
        $sourceTransaction
            ->setAction(PaymentMethodInterface::AUTHORIZE)
            ->setSuccessful(true)
            ->setActive(true)
            ->setAmount('1000')
            ->setCurrency('USD')
            ->setRequest(['AMT' => '1000']);

        $transaction = new PaymentTransaction();
        $transaction
            ->setSourcePaymentTransaction($sourceTransaction)
            ->setSuccessful(true)
            ->setActive(true);

        $this->gateway->expects($this->once())->method('request')->with('D')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->capture($transaction);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
        $this->assertEquals('reference', $transaction->getReference());
        $this->assertNotEmpty($transaction->getRequest());
        $this->assertArrayNotHasKey('CURRENCY', $transaction->getRequest());
        $this->assertArrayHasKey('AMT', $transaction->getRequest());
        $this->assertArrayHasKey('TENDER', $transaction->getRequest());
        $this->assertArrayHasKey('ORIGID', $transaction->getRequest());

        $this->assertTrue($sourceTransaction->isSuccessful());
        $this->assertFalse($sourceTransaction->isActive());
    }

    public function testPurchaseGetActionFromConfig()
    {
        $this->configureConfig(
            [$this->getConfigPrefix() . 'payment_action' => PaymentMethodInterface::AUTHORIZE]
        );

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::PURCHASE);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->purchase($transaction);

        $this->assertEquals(PaymentMethodInterface::AUTHORIZE, $transaction->getAction());
    }

    public function testPurchaseWithoutSourceGenerateSecureToken()
    {
        $this->configureConfig(
            [$this->getConfigPrefix() . 'payment_action' => PaymentMethodInterface::AUTHORIZE]
        );

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::PURCHASE);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->purchase($transaction);

        $this->assertFalse($transaction->isSuccessful());
        $this->assertTrue($transaction->isActive());
        $this->assertArrayHasKey('SECURETOKENID', $transaction->getRequest());
        $this->assertArrayHasKey('CREATESECURETOKEN', $transaction->getRequest());
    }

    public function testPurchaseWithSourceGenerateDoRequest()
    {
        $this->configureConfig(
            [
                $this->getConfigPrefix() . 'payment_action' => PaymentMethodInterface::AUTHORIZE,
                $this->getConfigPrefix() . 'authorization_for_required_amount' => true,
            ]
        );

        $sourceTransaction = new PaymentTransaction();

        $transaction = new PaymentTransaction();
        $transaction
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setSourcePaymentTransaction($sourceTransaction);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->purchase($transaction);

        $this->assertTrue($transaction->isSuccessful());
        $this->assertTrue($transaction->isActive());
        $this->assertArrayNotHasKey('SECURETOKENID', $transaction->getRequest());
        $this->assertArrayNotHasKey('CREATESECURETOKEN', $transaction->getRequest());
    }

    public function testValidateGenerateSecureToken()
    {
        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::VALIDATE);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->validate($transaction);

        $this->assertTrue($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());
        $this->assertArrayHasKey('SECURETOKENID', $transaction->getRequest());
        $this->assertArrayHasKey('CREATESECURETOKEN', $transaction->getRequest());
    }

    public function testValidateForceCurrencyAndAmount()
    {
        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::VALIDATE);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->validate($transaction);

        $this->assertTrue($transaction->isActive());
        $this->assertFalse($transaction->isSuccessful());
        $this->assertEquals(0, $transaction->getAmount());
        $this->assertEquals('USD', $transaction->getCurrency());
    }

    public function testSecureTokenResponseLimitedWithIdToKenAndFormAction()
    {
        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::VALIDATE);

        $secureToken = UUIDGenerator::v4();
        $secureTokenId = UUIDGenerator::v4();

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(
                [
                    'PNREF' => 'reference',
                    'RESULT' => '0',
                    'SECURETOKEN' => $secureToken,
                    'SECURETOKENID' => $secureTokenId,
                    'SHOULD_NOT_APPEAR_IN_RESPONSE' => 'AT_ALL',
                ]
            )
        );

        $this->gateway->expects($this->once())->method('getFormAction')->willReturn('url');

        $response = $this->method->validate($transaction);

        $this->assertEquals(
            [
                'formAction' => 'url',
                'SECURETOKEN' => $secureToken,
                'SECURETOKENID' => $secureTokenId,
            ],
            $response
        );
    }

    public function testSecureTokenOptions()
    {
        $transaction = new PaymentTransaction();
        $transaction
            ->setAction(PaymentMethodInterface::VALIDATE);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->router->expects($this->exactly(3))
            ->method('generate')
            ->withConsecutive(
                [
                    'orob2b_payment_callback_return',
                    [
                        'accessIdentifier' => $transaction->getAccessIdentifier(),
                    ],
                    true,
                ],
                [
                    'orob2b_payment_callback_error',
                    [
                        'accessIdentifier' => $transaction->getAccessIdentifier(),
                    ],
                    true,
                ],
                [
                    'orob2b_payment_callback_notify',
                    [
                        'accessIdentifier' => $transaction->getAccessIdentifier(),
                        'accessToken' => $transaction->getAccessToken(),
                    ],
                    true,
                ]
            )
            ->willReturnArgument(0);

        $this->method->validate($transaction);

        $this->assertArrayHasKey('SECURETOKENID', $transaction->getRequest());
        $this->assertArrayHasKey('AMT', $transaction->getRequest());
        $this->assertArrayHasKey('TENDER', $transaction->getRequest());
        $this->assertArrayHasKey('CURRENCY', $transaction->getRequest());
        $this->assertArrayHasKey('CREATESECURETOKEN', $transaction->getRequest());
        $this->assertArrayHasKey('SILENTTRAN', $transaction->getRequest());
        $this->assertArrayHasKey('RETURNURL', $transaction->getRequest());
        $this->assertArrayHasKey('ERRORURL', $transaction->getRequest());
    }

    public function testRequestPassButDoesNotContainsCredentials()
    {
        $this->configureConfig();

        $this->gateway->expects($this->once())->method('request')
            ->with(
                'A',
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->arrayHasKey('VENDOR'),
                    $this->arrayHasKey('USER'),
                    $this->arrayHasKey('PWD'),
                    $this->arrayHasKey('PARTNER')
                )
            )
            ->willReturn(new Response(['PNREF' => 'reference', 'RESULT' => '0']));

        $transaction = new PaymentTransaction();
        $transaction
            ->setAction(PaymentMethodInterface::VALIDATE);

        $this->method->validate($transaction);

        $this->assertArrayNotHasKey('VENDOR', $transaction->getRequest());
        $this->assertArrayNotHasKey('USER', $transaction->getRequest());
        $this->assertArrayNotHasKey('PWD', $transaction->getRequest());
        $this->assertArrayNotHasKey('PARTNER', $transaction->getRequest());
    }

    /**
     * @return string
     */
    abstract protected function getConfigPrefix();

    public function testIsApplicableWithoutContext()
    {
        $this->assertFalse($this->method->isApplicable([]));
    }

    public function testIsApplicableWithAllCountries()
    {
        $this->configureConfig(
            [$this->getConfigPrefix() . 'allowed_countries' => Configuration::ALLOWED_COUNTRIES_ALL]
        );

        $this->assertTrue($this->method->isApplicable([]));
    }

    public function testIsApplicableWithSelectedCountriesNotMatch()
    {
        $this->configureConfig(
            [
                $this->getConfigPrefix() . 'allowed_countries' => Configuration::ALLOWED_COUNTRIES_SELECTED,
                $this->getConfigPrefix() . 'selected_countries' => ['US'],
            ]
        );

        $this->assertFalse($this->method->isApplicable(['country' => 'UK']));
    }

    public function testIsApplicableWithSelectedCountries()
    {
        $this->configureConfig(
            [
                $this->getConfigPrefix() . 'allowed_countries' => Configuration::ALLOWED_COUNTRIES_SELECTED,
                $this->getConfigPrefix() . 'selected_countries' => ['US'],
            ]
        );

        $this->assertTrue($this->method->isApplicable(['country' => 'US']));
    }

    public function testCompleteTransactionWithReferenceAlreadyProcessed()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setReference('PNREF');

        $this->assertFalse($this->method->completeTransaction($paymentTransaction, []));
    }

    public function testOnNotify()
    {
        $paymentTransaction = new PaymentTransaction();

        $this->assertEmpty($paymentTransaction->getReference());
        $this->method->completeTransaction($paymentTransaction, ['PNREF' => 'ref']);
        $this->assertEquals('ref', $paymentTransaction->getReference());
    }

    public function testOnNotifySuccessfulFromResponse()
    {
        $paymentTransaction = new PaymentTransaction();

        $this->assertFalse($paymentTransaction->isSuccessful());
        $this->method->completeTransaction($paymentTransaction, ['RESULT' => ResponseStatusMap::APPROVED]);
        $this->assertTrue($paymentTransaction->isSuccessful());
    }

    public function testOnNotifyActiveFromResponse()
    {
        $paymentTransaction = new PaymentTransaction();

        $this->assertFalse($paymentTransaction->isActive());
        $this->method->completeTransaction($paymentTransaction, ['RESULT' => ResponseStatusMap::APPROVED]);
        $this->assertTrue($paymentTransaction->isActive());
    }

    public function testOnNotifyAppendResponseData()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['existing' => 'response']);

        $this->method->completeTransaction($paymentTransaction, ['RESULT' => ResponseStatusMap::APPROVED]);

        $this->assertEquals(
            ['existing' => 'response', 'RESULT' => ResponseStatusMap::APPROVED],
            $paymentTransaction->getResponse()
        );
    }

    public function testOnNotifyWithCharge()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setActive(PaymentMethodInterface::CHARGE);

        $this->assertEmpty($paymentTransaction->getReference());
        $this->method->completeTransaction($paymentTransaction, ['PNREF' => 'ref']);
        $this->assertEquals('ref', $paymentTransaction->getReference());
        $this->assertFalse($paymentTransaction->isActive());
    }

}

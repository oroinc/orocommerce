<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;
use Oro\Bundle\PayPalBundle\Method\PayflowGateway;
use Oro\Bundle\PayPalBundle\Method\PayPalPaymentsPro;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
abstract class AbstractPayflowGatewayTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait, EntityTrait;

    const PROXY_HOST = '112.33.44.55';
    const PROXY_PORT = 7777;

    /** @var Gateway|\PHPUnit_Framework_MockObject_MockObject */
    protected $gateway;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var PayflowGateway|PayPalPaymentsPro */
    protected $method;

    /** @var PayflowGatewayConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
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
            $this->getMock('Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface');

        $this->method = $this->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensionAlias()
    {
        return OroPayPalExtension::ALIAS;
    }

    /**
     * @return PayflowGateway|PayPalPaymentsPro
     */
    abstract protected function getMethod();

    protected function tearDown()
    {
        unset($this->router, $this->gateway, $this->paymentConfig, $this->method);
    }

    public function testExecute()
    {
        $sourcePaymentTransaction = new PaymentTransaction();
        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::CHARGE);
        $transaction->setSourcePaymentTransaction($sourcePaymentTransaction);

        $this->configureCredentials();

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
            $this->method->execute($transaction->getAction(), $transaction)
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

        $this->method->execute($transaction->getAction(), $transaction);
    }

    public function testIsEnabled()
    {
        $this->paymentConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

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
            [true, PayflowGateway::COMPLETE],
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
        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($configValue);

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

    protected function configureCredentials()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getCredentials')
            ->willReturn([
                Option\Vendor::VENDOR => 'vendor',
                Option\User::USER => 'user',
                Option\Password::PASSWORD => 'password',
                Option\Partner::PARTNER => 'partner',
            ]);
    }

    public function testAuthorizeDoNotPerformRequestIfAmountAuthorizationIsNotRequiredAndValidationExists()
    {
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
        $this->configureCredentials();

        $this->paymentConfig->expects($this->never())
            ->method('isAuthorizationForRequiredAmountEnabled')
            ->willReturn(false);

        $transaction = new PaymentTransaction();

        $this->gateway->expects($this->once())->method('request')->with('A')
            ->willReturn(new Response(['PNREF' => 'reference', 'RESULT' => '0']));

        $this->method->authorize($transaction);

        $this->assertEquals('reference', $transaction->getReference());
    }

    public function testAuthorizePerformRequest()
    {
        $this->configureCredentials();

        $this->paymentConfig->expects($this->never())
            ->method('isAuthorizationForRequiredAmountEnabled')
            ->willReturn(true);

        $transaction = new PaymentTransaction();

        $this->gateway->expects($this->once())->method('request')->with('A')
            ->willReturn(new Response(['PNREF' => 'reference', 'RESULT' => '0']));

        $this->method->authorize($transaction);

        $this->assertEquals('reference', $transaction->getReference());
    }

    public function testChargeWithoutSourceTransaction()
    {
        $transaction = new PaymentTransaction();

        $this->configureCredentials();

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
        $this->configureCredentials();

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
        $this->configureCredentials();

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
        $this->configureCredentials();

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
        $this->configureCredentials();

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
        $this->configureCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

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
        $this->configureCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

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
        $this->configureCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $this->paymentConfig->expects($this->once())
            ->method('isAuthorizationForRequiredAmountEnabled')
            ->willReturn(true);

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

    public function testPurchaseWithSourceAndError()
    {
        $this->configureCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $this->paymentConfig->expects($this->once())
            ->method('isAuthorizationForRequiredAmountEnabled')
            ->willReturn(true);

        $sourceTransaction = new PaymentTransaction();

        $transaction = new PaymentTransaction();
        $transaction
            ->setAction(PaymentMethodInterface::PURCHASE)
            ->setSourcePaymentTransaction($sourceTransaction);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '12'])
        );

        $this->method->purchase($transaction);

        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
        $this->assertArrayNotHasKey('SECURETOKENID', $transaction->getRequest());
        $this->assertArrayNotHasKey('CREATESECURETOKEN', $transaction->getRequest());
    }

    public function testValidateGenerateSecureToken()
    {
        $this->configureCredentials();

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
        $this->configureCredentials();

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
        $this->configureCredentials();

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
        $this->configureCredentials();

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
                    UrlGeneratorInterface::ABSOLUTE_URL
                ],
                [
                    'orob2b_payment_callback_error',
                    [
                        'accessIdentifier' => $transaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ],
                [
                    'orob2b_payment_callback_notify',
                    [
                        'accessIdentifier' => $transaction->getAccessIdentifier(),
                        'accessToken' => $transaction->getAccessToken(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
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
        $this->configureCredentials();

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
     * @param bool $useProxy
     */
    protected function configureProxyOptions($useProxy)
    {
        $this->configureCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('isUseProxyEnabled')
            ->willReturn($useProxy);

        $this->paymentConfig->expects($useProxy ? $this->once() : $this->never())
            ->method('getProxyHost')
            ->willReturn(self::PROXY_HOST);

        $this->paymentConfig->expects($useProxy ? $this->once() : $this->never())
            ->method('getProxyPort')
            ->willReturn(self::PROXY_PORT);
    }

    public function testProxyAddressIsNotSetWhenUseProxyConfigurationIsDisabled()
    {
        $this->configureProxyOptions(false);

        $this->gateway
            ->expects($this->never())
            ->method('setProxySettings');

        $this->gateway
            ->expects($this->once())
            ->method('request')
            ->willReturn(new Response());

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::AUTHORIZE);

        $this->method->execute($transaction->getAction(), $transaction);
    }

    public function testProxyAddressIsSetWhenUseProxyConfigurationIsEnabled()
    {
        $this->configureProxyOptions(true);

        $this->gateway
            ->expects($this->once())
            ->method('setProxySettings')
            ->with(self::PROXY_HOST, self::PROXY_PORT);

        $this->gateway
            ->expects($this->once())
            ->method('request')
            ->willReturn(new Response());

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::AUTHORIZE);

        $this->method->execute($transaction->getAction(), $transaction);
    }

    public function testVerbosityOptionWithDebug()
    {
        $this->configureCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('isDebugModeEnabled')
            ->willReturn(true);

        $constraint = $this->arrayHasKey(Option\Verbosity::VERBOSITY);

        $this->gateway
            ->expects($this->once())
            ->method('request')
            ->with($this->anything(), $constraint)
            ->willReturn(new Response());

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::AUTHORIZE);

        $this->method->execute($transaction->getAction(), $transaction);
    }

    public function testVerbosityOptionWithoutDebug()
    {
        $this->configureCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('isDebugModeEnabled')
            ->willReturn(false);

        $constraint = $this->logicalNot($this->arrayHasKey(Option\Verbosity::VERBOSITY));

        $this->gateway
            ->expects($this->once())
            ->method('request')
            ->with($this->anything(), $constraint)
            ->willReturn(new Response());

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::AUTHORIZE);

        $this->method->execute($transaction->getAction(), $transaction);
    }

    /**
     * @return array
     */
    public function sslVerificationEnabledDataProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider sslVerificationEnabledDataProvider
     *
     * @param bool $sslVerificationEnabled
     */
    public function testSslVerificationOptionValueIsPassedToPayFlow($sslVerificationEnabled)
    {
        $this->configureCredentials();

        $this->paymentConfig->expects($this->once())
            ->method('isSslVerificationEnabled')
            ->willReturn($sslVerificationEnabled);

        $this->gateway
            ->expects($this->once())
            ->method('setSslVerificationEnabled')
            ->with($sslVerificationEnabled);

        $this->gateway
            ->expects($this->once())
            ->method('request')
            ->willReturn(new Response());

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::AUTHORIZE);
        $this->method->execute($transaction->getAction(), $transaction);
    }

    /**
     * @return string
     */
    abstract protected function getConfigPrefix();

    public function testIsApplicableWithoutContext()
    {
        $this->assertFalse($this->method->isApplicable(['currency' => ['USD']]));
    }

    public function testIsApplicableWithAllCountries()
    {
        $context = ['currency' => 'USD'];
        $this->paymentConfig->expects($this->once())
            ->method('isCountryApplicable')
            ->with($context)
            ->willReturn(true);

        $this->paymentConfig->expects($this->once())
            ->method('isCurrencyApplicable')
            ->with($context)
            ->willReturn(true);

        $this->assertTrue($this->method->isApplicable($context));
    }

    public function testIsApplicableWithSelectedCountriesNotMatch()
    {
        $context = ['country' => 'UK'];
        $this->paymentConfig->expects($this->once())
            ->method('isCountryApplicable')
            ->with($context)
            ->willReturn(false);

        $this->paymentConfig->expects($this->never())
            ->method('isCurrencyApplicable');

        $this->assertFalse($this->method->isApplicable($context));
    }

    public function testIsApplicableWithSelectedCountries()
    {
        $context = ['country' => 'US', 'currency' => 'USD'];
        $this->paymentConfig->expects($this->once())
            ->method('isCountryApplicable')
            ->with($context)
            ->willReturn(true);

        $this->paymentConfig->expects($this->once())
            ->method('isCurrencyApplicable')
            ->with($context)
            ->willReturn(true);

        $this->assertTrue($this->method->isApplicable(['country' => 'US', 'currency' => 'USD']));
    }

    public function testComplete()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['PNREF' => 'ref']);

        $this->assertEmpty($paymentTransaction->getReference());
        $this->method->complete($paymentTransaction);
        $this->assertEquals('ref', $paymentTransaction->getReference());
    }

    public function testCompleteSuccessfulFromResponse()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['RESULT' => ResponseStatusMap::APPROVED]);

        $this->assertFalse($paymentTransaction->isSuccessful());
        $this->method->complete($paymentTransaction);
        $this->assertTrue($paymentTransaction->isSuccessful());
    }

    public function testCompleteActiveFromResponse()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['RESULT' => ResponseStatusMap::APPROVED]);

        $this->assertFalse($paymentTransaction->isActive());
        $this->method->complete($paymentTransaction);
        $this->assertTrue($paymentTransaction->isActive());
    }

    public function testCompleteWithCharge()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['RESULT' => ResponseStatusMap::APPROVED, 'PNREF' => 'ref']);
        $paymentTransaction->setAction(PaymentMethodInterface::CHARGE);

        $this->assertEmpty($paymentTransaction->getReference());
        $this->method->complete($paymentTransaction);
        $this->assertEquals('ref', $paymentTransaction->getReference());
        $this->assertFalse($paymentTransaction->isActive());
    }
}

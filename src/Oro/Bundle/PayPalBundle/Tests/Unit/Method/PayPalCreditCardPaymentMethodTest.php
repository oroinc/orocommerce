<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseStatusMap;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PayPalCreditCardPaymentMethodTest extends \PHPUnit\Framework\TestCase
{
    private const PROXY_HOST = '112.33.44.55';
    private const PROXY_PORT = 7777;

    /** @var Gateway|\PHPUnit\Framework\MockObject\MockObject */
    private $gateway;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var PayPalCreditCardConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentConfig;

    /** @var PayPalCreditCardPaymentMethod */
    private $method;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->paymentConfig = $this->createMock(PayPalCreditCardConfigInterface::class);

        $this->method = new PayPalCreditCardPaymentMethod($this->gateway, $this->paymentConfig, $this->router);
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
                'message' => 'Approved',
                'successful' => true,
            ],
            $this->method->execute($transaction->getAction(), $transaction)
        );

        $this->assertTrue($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
    }

    public function testExecuteException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported action "wrong_action"');

        $transaction = new PaymentTransaction();
        $transaction->setAction('wrong_action');

        $this->method->execute($transaction->getAction(), $transaction);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(bool $expected, string $actionName)
    {
        $this->assertEquals($expected, $this->method->supports($actionName));
    }

    public function supportsDataProvider(): array
    {
        return [
            [true, PaymentMethodInterface::AUTHORIZE],
            [true, PaymentMethodInterface::CAPTURE],
            [true, PaymentMethodInterface::CHARGE],
            [true, PaymentMethodInterface::PURCHASE],
            [true, PayPalCreditCardPaymentMethod::COMPLETE],
        ];
    }

    /**
     * @dataProvider validateSupportsDataProvider
     */
    public function testSupportsValidate(bool $expected, bool $configValue)
    {
        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($configValue);

        $this->assertEquals($expected, $this->method->supports(PaymentMethodInterface::VALIDATE));
    }

    public function validateSupportsDataProvider(): array
    {
        return [
            [true, true],
            [false, false],
        ];
    }

    private function configureCredentials(): void
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

    /**
     * @dataProvider responseWithErrorDataProvider
     */
    public function testChargeWithError(string $responseMessage, string $expectedMessage)
    {
        $this->configureCredentials();

        $transaction = new PaymentTransaction();
        $transaction
            ->setSuccessful(true)
            ->setActive(true);

        $this->gateway->expects($this->once())->method('request')->with('S')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '-1', 'RESPMSG' => $responseMessage])
        );

        $result = $this->method->charge($transaction);

        $this->assertSame($expectedMessage, $result['message']);
        $this->assertFalse($result['successful']);

        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
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

    /**
     * @dataProvider responseWithErrorDataProvider
     */
    public function testCaptureWithError(string $responseMessage, string $expectedMessage)
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

        $this->gateway->expects($this->once())->method('request')->with('D')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '-1', 'RESPMSG' => $responseMessage])
        );

        $result = $this->method->capture($transaction);

        $this->assertSame($expectedMessage, $result['message']);
        $this->assertFalse($result['successful']);

        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());
        $this->assertNotEmpty($transaction->getRequest());

        $this->assertTrue($sourceTransaction->isSuccessful());
        $this->assertTrue($sourceTransaction->isActive());
    }

    public function responseWithErrorDataProvider(): array
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
                    'oro_payment_callback_return',
                    [
                        'accessIdentifier' => $transaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ],
                [
                    'oro_payment_callback_error',
                    [
                        'accessIdentifier' => $transaction->getAccessIdentifier(),
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ],
                [
                    'oro_payment_callback_notify',
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

    private function configureProxyOptions(bool $useProxy): void
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

    public function sslVerificationEnabledDataProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider sslVerificationEnabledDataProvider
     */
    public function testSslVerificationOptionValueIsPassedToPayFlow(bool $sslVerificationEnabled)
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
     * @dataProvider isApplicableProvider
     */
    public function testIsApplicable(int $value, bool $expectedResult)
    {
        $context = $this->createMock(PaymentContext::class);
        $context->expects($this->once())
            ->method('getTotal')
            ->willReturn($value);

        $this->assertEquals($expectedResult, $this->method->isApplicable($context));
    }

    public function isApplicableProvider(): array
    {
        return [
            [0, false],
            [42, true],
        ];
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

    public function testGetIdentifier()
    {
        $this->paymentConfig->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('payflow_express_checkout');
        $this->assertSame('payflow_express_checkout', $this->method->getIdentifier());
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method;

use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayPalCreditCardPaymentMethod;
use Oro\Bundle\PayPalBundle\Method\Transaction\TransactionOptionProvider;
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

    /** @var TransactionOptionProvider */
    private $transactionOptionProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->paymentConfig = $this->createMock(PayPalCreditCardConfigInterface::class);
        $this->transactionOptionProvider = $this->createMock(TransactionOptionProvider::class);

        $this->method = new PayPalCreditCardPaymentMethod(
            $this->gateway,
            $this->paymentConfig,
            $this->router,
            $this->transactionOptionProvider
        );
    }

    public function testExecute()
    {
        $sourcePaymentTransaction = new PaymentTransaction();
        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::CHARGE);
        $transaction->setSourcePaymentTransaction($sourcePaymentTransaction);

        $this->configureCredentials();
        $this->configureTransactionProvider();

        $this->gateway->expects($this->any())
            ->method('request')
            ->with('S')
            ->willReturn(new Response(['PNREF' => 'reference', 'RESULT' => '0']));

        $this->gateway->expects($this->any())
            ->method('setTestMode')
            ->with(false);

        self::assertEquals(
            [
                'message' => 'Approved',
                'successful' => true,
            ],
            $this->method->execute($transaction->getAction(), $transaction)
        );

        self::assertTrue($transaction->isSuccessful());
        self::assertFalse($transaction->isActive());
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
        self::assertEquals($expected, $this->method->supports($actionName));
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

        self::assertEquals($expected, $this->method->supports(PaymentMethodInterface::VALIDATE));
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

    private function configureTransactionProvider(): void
    {
        $this->transactionOptionProvider->expects(self::once())
            ->method('getOrderOptions')
            ->willReturn([
                Gateway\Option\Comment::COMMENT1 => 'comment',
                Gateway\Option\Purchase::PONUM => 'ponum',
            ]);

        $this->transactionOptionProvider->expects(self::once())
            ->method('getShippingAddressOptions')
            ->willReturn([
                Option\ShippingAddress::SHIPTOFIRSTNAME => 'First',
                Option\ShippingAddress::SHIPTOMIDDLENAME => 'A.',
                Option\ShippingAddress::SHIPTOLASTNAME => 'Name',
                Option\ShippingAddress::SHIPTOSTREET => 'Main str.',
                Option\ShippingAddress::SHIPTOSTREET2 => 'Main2 str.',
                Option\ShippingAddress::SHIPTOCITY => 'City',
                Option\ShippingAddress::SHIPTOSTATE => 'CTY',
                Option\ShippingAddress::SHIPTOZIP => '112233',
                Option\ShippingAddress::SHIPTOCOUNTRY => 'CTR',
                Option\ShippingAddress::SHIPTOCOMPANY => 'Oro Inc.',
                Option\ShippingAddress::SHIPTOPHONE => '111-222-333',
            ]);

        $this->transactionOptionProvider->expects(self::once())
            ->method('getBillingAddressOptions')
            ->willReturn([
                Option\BillingAddress::BILLTOCOMPANY => 'Oro Inc.',
                Option\BillingAddress::BILLTOPHONENUM => '111-222-333',
            ]);

        $this->transactionOptionProvider->expects(self::once())
            ->method('getCustomerUserOptions')
            ->willReturn([
                Option\ShippingAddress::SHIPTOEMAIL => 'email@example.com',
                Option\BillingAddress::BILLTOEMAIL => 'email@example.com',
                Gateway\Option\Customer::EMAIL => 'email@example.com',
                Gateway\Option\Customer::CUSTCODE => '1',
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

        self::assertEquals($sourceTransaction->getReference(), $transaction->getReference());
        self::assertEquals($sourceTransaction->getCurrency(), $transaction->getCurrency());
        self::assertEquals($sourceTransaction->isSuccessful(), $transaction->isSuccessful());
        self::assertEquals($sourceTransaction->isActive(), $transaction->isActive());
        self::assertEmpty($transaction->getRequest());
        self::assertEmpty($transaction->getResponse());
    }

    public function testAuthorizePerformRequestIfAmountAuthorizationIsNotRequiredAndValidationIsMissing()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

        $this->paymentConfig->expects($this->never())
            ->method('isAuthorizationForRequiredAmountEnabled')
            ->willReturn(false);

        $transaction = new PaymentTransaction();

        $this->gateway->expects($this->once())->method('request')->with('A')
            ->willReturn(new Response(['PNREF' => 'reference', 'RESULT' => '0']));

        $this->method->authorize($transaction);

        self::assertEquals('reference', $transaction->getReference());
    }

    public function testAuthorizePerformRequest()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

        $this->paymentConfig->expects($this->never())
            ->method('isAuthorizationForRequiredAmountEnabled')
            ->willReturn(true);

        $transaction = new PaymentTransaction();

        $this->gateway->expects($this->once())->method('request')->with('A')
            ->willReturn(new Response(['PNREF' => 'reference', 'RESULT' => '0']));

        $this->method->authorize($transaction);

        self::assertEquals('reference', $transaction->getReference());
    }

    public function testChargeWithoutSourceTransaction()
    {
        $transaction = new PaymentTransaction();

        $this->configureCredentials();
        $this->configureTransactionProvider();

        $this->gateway->expects($this->once())->method('request')->with('S')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $response = $this->method->charge($transaction);

        self::assertEquals('reference', $transaction->getReference());
        self::assertTrue($transaction->isSuccessful());
        self::assertTrue($transaction->isActive());
        self::assertArrayHasKey('successful', $response);
        self::assertTrue($response['successful']);
    }

    public function testChargeDeactivateSourceTransactionIfItsNotValidationOne()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

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

        self::assertTrue($transaction->isSuccessful());
        self::assertFalse($transaction->isActive());
        self::assertTrue($sourceTransaction->isSuccessful());
        self::assertFalse($sourceTransaction->isActive());
    }

    public function testChargeDoNotChangeValidateSourceTransactionState()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

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

        self::assertTrue($transaction->isSuccessful());
        self::assertFalse($transaction->isActive());
        self::assertTrue($sourceTransaction->isSuccessful());
        self::assertTrue($sourceTransaction->isActive());
    }

    /**
     * @dataProvider responseWithErrorDataProvider
     */
    public function testChargeWithError(string $responseMessage, string $expectedMessage)
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

        $transaction = new PaymentTransaction();
        $transaction
            ->setSuccessful(true)
            ->setActive(true);

        $this->gateway->expects($this->once())->method('request')->with('S')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '-1', 'RESPMSG' => $responseMessage])
        );

        $result = $this->method->charge($transaction);

        self::assertSame($expectedMessage, $result['message']);
        self::assertFalse($result['successful']);

        self::assertFalse($transaction->isSuccessful());
        self::assertFalse($transaction->isActive());
    }

    public function testCaptureDoChargeIfSourceAuthorizationIsValidationTransactionClone()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

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

        self::assertTrue($transaction->isSuccessful());
        self::assertFalse($transaction->isActive());
        self::assertTrue($sourceTransaction->isSuccessful());
        self::assertFalse($sourceTransaction->isActive());
    }

    public function testCaptureWithoutSourceTransaction()
    {
        $transaction = new PaymentTransaction();
        $transaction
            ->setSuccessful(true)
            ->setActive(true);

        $this->gateway->expects($this->never())->method($this->anything());

        $this->method->capture($transaction);

        self::assertFalse($transaction->isSuccessful());
        self::assertFalse($transaction->isActive());
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

        self::assertSame($expectedMessage, $result['message']);
        self::assertFalse($result['successful']);

        self::assertFalse($transaction->isSuccessful());
        self::assertFalse($transaction->isActive());
        self::assertNotEmpty($transaction->getRequest());

        self::assertTrue($sourceTransaction->isSuccessful());
        self::assertTrue($sourceTransaction->isActive());
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

        self::assertTrue($transaction->isSuccessful());
        self::assertFalse($transaction->isActive());
        self::assertEquals('reference', $transaction->getReference());
        self::assertNotEmpty($transaction->getRequest());
        self::assertArrayNotHasKey('CURRENCY', $transaction->getRequest());
        self::assertArrayHasKey('AMT', $transaction->getRequest());
        self::assertArrayHasKey('TENDER', $transaction->getRequest());
        self::assertArrayHasKey('ORIGID', $transaction->getRequest());
        self::assertTrue($sourceTransaction->isSuccessful());
        self::assertFalse($sourceTransaction->isActive());
    }

    public function testPurchaseGetActionFromConfig()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::PURCHASE);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->purchase($transaction);

        self::assertEquals(PaymentMethodInterface::AUTHORIZE, $transaction->getAction());
    }

    public function testPurchaseWithoutSourceGenerateSecureToken()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

        $this->paymentConfig->expects($this->once())
            ->method('getPurchaseAction')
            ->willReturn(PaymentMethodInterface::AUTHORIZE);

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::PURCHASE);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->purchase($transaction);

        self::assertFalse($transaction->isSuccessful());
        self::assertTrue($transaction->isActive());
        self::assertArrayHasKey('SECURETOKENID', $transaction->getRequest());
        self::assertArrayHasKey('CREATESECURETOKEN', $transaction->getRequest());
    }

    public function testPurchaseWithSourceGenerateDoRequest()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

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

        self::assertTrue($transaction->isSuccessful());
        self::assertTrue($transaction->isActive());
        self::assertArrayNotHasKey('SECURETOKENID', $transaction->getRequest());
        self::assertArrayNotHasKey('CREATESECURETOKEN', $transaction->getRequest());
    }

    public function testPurchaseWithSourceAndError()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

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

        self::assertFalse($transaction->isSuccessful());
        self::assertFalse($transaction->isActive());
        self::assertArrayNotHasKey('SECURETOKENID', $transaction->getRequest());
        self::assertArrayNotHasKey('CREATESECURETOKEN', $transaction->getRequest());
    }

    public function testValidateGenerateSecureToken()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::VALIDATE);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->validate($transaction);

        self::assertTrue($transaction->isActive());
        self::assertFalse($transaction->isSuccessful());
        self::assertArrayHasKey('SECURETOKENID', $transaction->getRequest());
        self::assertArrayHasKey('CREATESECURETOKEN', $transaction->getRequest());
    }

    public function testValidateForceCurrencyAndAmount()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

        $transaction = new PaymentTransaction();
        $transaction->setAction(PaymentMethodInterface::VALIDATE);

        $this->gateway->expects($this->once())->method('request')->with('A')->willReturn(
            new Response(['PNREF' => 'reference', 'RESULT' => '0'])
        );

        $this->method->validate($transaction);

        self::assertTrue($transaction->isActive());
        self::assertFalse($transaction->isSuccessful());
        self::assertEquals(0, $transaction->getAmount());
        self::assertEquals('USD', $transaction->getCurrency());
    }

    public function testSecureTokenResponseLimitedWithIdToKenAndFormAction()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

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

        self::assertEquals(
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
        $this->configureTransactionProvider();

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

        self::assertArrayHasKey('SECURETOKENID', $transaction->getRequest());
        self::assertArrayHasKey('AMT', $transaction->getRequest());
        self::assertArrayHasKey('TENDER', $transaction->getRequest());
        self::assertArrayHasKey('CURRENCY', $transaction->getRequest());
        self::assertArrayHasKey('CREATESECURETOKEN', $transaction->getRequest());
        self::assertArrayHasKey('SILENTTRAN', $transaction->getRequest());
        self::assertArrayHasKey('RETURNURL', $transaction->getRequest());
        self::assertArrayHasKey('ERRORURL', $transaction->getRequest());
    }

    public function testRequestPassButDoesNotContainsCredentials()
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

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

        self::assertArrayNotHasKey('VENDOR', $transaction->getRequest());
        self::assertArrayNotHasKey('USER', $transaction->getRequest());
        self::assertArrayNotHasKey('PWD', $transaction->getRequest());
        self::assertArrayNotHasKey('PARTNER', $transaction->getRequest());
    }

    private function configureProxyOptions(bool $useProxy): void
    {
        $this->configureCredentials();
        $this->configureTransactionProvider();

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
        $this->configureTransactionProvider();

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
        $this->configureTransactionProvider();

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
        $this->configureTransactionProvider();

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

        self::assertEquals($expectedResult, $this->method->isApplicable($context));
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

        self::assertEmpty($paymentTransaction->getReference());
        $this->method->complete($paymentTransaction);
        self::assertEquals('ref', $paymentTransaction->getReference());
    }

    public function testCompleteSuccessfulFromResponse()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['RESULT' => ResponseStatusMap::APPROVED]);

        self::assertFalse($paymentTransaction->isSuccessful());
        $this->method->complete($paymentTransaction);
        self::assertTrue($paymentTransaction->isSuccessful());
    }

    public function testCompleteActiveFromResponse()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['RESULT' => ResponseStatusMap::APPROVED]);

        self::assertFalse($paymentTransaction->isActive());
        $this->method->complete($paymentTransaction);
        self::assertTrue($paymentTransaction->isActive());
    }

    public function testCompleteWithCharge()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['RESULT' => ResponseStatusMap::APPROVED, 'PNREF' => 'ref']);
        $paymentTransaction->setAction(PaymentMethodInterface::CHARGE);

        self::assertEmpty($paymentTransaction->getReference());
        $this->method->complete($paymentTransaction);
        self::assertEquals('ref', $paymentTransaction->getReference());
        self::assertFalse($paymentTransaction->isActive());
    }

    public function testGetIdentifier()
    {
        $this->paymentConfig->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('payflow_express_checkout');
        self::assertSame('payflow_express_checkout', $this->method->getIdentifier());
    }
}

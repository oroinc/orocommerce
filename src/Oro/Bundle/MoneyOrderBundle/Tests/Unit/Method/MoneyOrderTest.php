<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MoneyOrderTest extends \PHPUnit\Framework\TestCase
{
    /** @var MoneyOrderConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var MoneyOrder */
    private $method;

    protected function setUp(): void
    {
        $this->config = $this->createMock(MoneyOrderConfigInterface::class);

        $this->method = new MoneyOrder($this->config);
    }

    public function testExecutePurchaseViaExecute()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());

        $this->assertEquals(['successful' => true], $this->method->execute(MoneyOrder::PURCHASE, $transaction));
        $this->assertTrue($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertEquals(MoneyOrder::PENDING, $transaction->getAction());
    }

    public function testExecutePurchaseDirectly()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());

        $this->assertEquals(['successful' => true], $this->method->purchase($transaction));
        $this->assertTrue($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertEquals(MoneyOrder::PENDING, $transaction->getAction());
    }

    public function testExecuteCaptureViaExecute()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());

        $this->assertEquals(['successful' => true], $this->method->execute(MoneyOrder::CAPTURE, $transaction));
        $this->assertTrue($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertEquals(MoneyOrder::CAPTURE, $transaction->getAction());
    }

    public function testExecuteCaptureDirectly()
    {
        $transaction = new PaymentTransaction();
        $this->assertFalse($transaction->isSuccessful());
        $this->assertFalse($transaction->isActive());

        $this->assertEquals(['successful' => true], $this->method->capture($transaction));
        $this->assertTrue($transaction->isActive());
        $this->assertTrue($transaction->isSuccessful());
        $this->assertEquals(MoneyOrder::CAPTURE, $transaction->getAction());
    }

    public function testExecuteNotSupported()
    {
        $this->config->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('MoneyOrder');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"MoneyOrder" payment method "not_supported" action is not supported');

        $this->method->execute('not_supported', new PaymentTransaction());
    }

    public function testGetSourceAction()
    {
        $this->assertEquals('pending', $this->method->getSourceAction());
    }

    public function testUseSourcePaymentTransaction()
    {
        $this->assertTrue($this->method->useSourcePaymentTransaction());
    }

    public function testGetIdentifier()
    {
        $identifier = 'id';

        $this->config->expects(self::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        $this->assertEquals($identifier, $this->method->getIdentifier());
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
            [false, MoneyOrder::AUTHORIZE],
            [true, MoneyOrder::CAPTURE],
            [false, MoneyOrder::CHARGE],
            [false, MoneyOrder::VALIDATE],
            [true, MoneyOrder::PURCHASE],
        ];
    }

    public function testIsApplicable()
    {
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertTrue($this->method->isApplicable($context));
    }
}

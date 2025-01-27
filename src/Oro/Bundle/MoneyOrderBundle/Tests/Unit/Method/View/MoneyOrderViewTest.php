<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\View;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class MoneyOrderViewTest extends \PHPUnit\Framework\TestCase
{
    /** @var MoneyOrderConfig|\PHPUnit\Framework\MockObject\MockObject */
    private $config;

    /** @var MoneyOrderView */
    private $methodView;

    #[\Override]
    protected function setUp(): void
    {
        $this->config = $this->createMock(MoneyOrderConfig::class);

        $this->methodView = new MoneyOrderView($this->config);
    }

    public function testGetFrontendApiOptions(): void
    {
        $payTo = 'Pay To';
        $sendTo = 'Send To';

        $this->config->expects(self::once())
            ->method('getPayTo')
            ->willReturn($payTo);
        $this->config->expects(self::once())
            ->method('getSendTo')
            ->willReturn($sendTo);

        $this->assertEquals(
            ['payTo' => $payTo, 'sendTo' => $sendTo],
            $this->methodView->getFrontendApiOptions($this->createMock(PaymentContextInterface::class))
        );
    }

    public function testGetOptions(): void
    {
        $payTo = 'Pay To';
        $sendTo = 'Send To';

        $this->config->expects(self::once())
            ->method('getPayTo')
            ->willReturn($payTo);
        $this->config->expects(self::once())
            ->method('getSendTo')
            ->willReturn($sendTo);

        $this->assertEquals(
            ['pay_to' => $payTo, 'send_to' => $sendTo],
            $this->methodView->getOptions($this->createMock(PaymentContextInterface::class))
        );
    }

    public function testGetBlock(): void
    {
        $this->assertEquals('_payment_methods_money_order_widget', $this->methodView->getBlock());
    }

    public function testGetLabel(): void
    {
        $label = 'label';

        $this->config->expects(self::once())
            ->method('getLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->methodView->getLabel());
    }

    public function testShortGetLabel(): void
    {
        $label = 'label';

        $this->config->expects(self::once())
            ->method('getShortLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->methodView->getShortLabel());
    }

    public function testGetAdminLabel(): void
    {
        $label = 'label';

        $this->config->expects(self::once())
            ->method('getAdminLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->methodView->getAdminLabel());
    }

    public function testGetPaymentMethodIdentifier(): void
    {
        $identifier = 'id';

        $this->config->expects(self::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        $this->assertEquals($identifier, $this->methodView->getPaymentMethodIdentifier());
    }
}

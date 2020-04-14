<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\View;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class MoneyOrderViewTest extends \PHPUnit\Framework\TestCase
{
    /** @var MoneyOrderView */
    protected $methodView;

    /** @var MoneyOrderConfig|\PHPUnit\Framework\MockObject\MockObject */
    protected $config;

    protected function setUp(): void
    {
        $this->config = $this->createMock(MoneyOrderConfig::class);

        $this->methodView = new MoneyOrderView($this->config);
    }

    public function testGetOptions()
    {
        $data = ['pay_to' => 'Pay To', 'send_to' => 'Send To'];

        $this->config->expects(static::once())
            ->method('getPayTo')
            ->willReturn($data['pay_to']);
        $this->config->expects(static::once())
            ->method('getSendTo')
            ->willReturn($data['send_to']);

        /** @var PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $this->assertEquals($data, $this->methodView->getOptions($context));
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_money_order_widget', $this->methodView->getBlock());
    }

    public function testGetLabel()
    {
        $label = 'label';

        $this->config->expects(static::once())
            ->method('getLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->methodView->getLabel());
    }

    public function testShortGetLabel()
    {
        $label = 'label';

        $this->config->expects(static::once())
            ->method('getShortLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->methodView->getShortLabel());
    }

    public function testGetAdminLabel()
    {
        $label = 'label';

        $this->config->expects(static::once())
            ->method('getAdminLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->methodView->getAdminLabel());
    }

    public function testGetPaymentMethodIdentifier()
    {
        $identifier = 'id';

        $this->config->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        $this->assertEquals($identifier, $this->methodView->getPaymentMethodIdentifier());
    }
}

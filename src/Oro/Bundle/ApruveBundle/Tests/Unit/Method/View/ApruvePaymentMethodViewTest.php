<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\View;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfig;
use Oro\Bundle\ApruveBundle\Method\View\ApruvePaymentMethodView;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class ApruveViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruvePaymentMethodView
     */
    private $methodView;

    /**
     * @var ApruveConfig|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->config = $this->createMock(ApruveConfig::class);

        $this->methodView = new ApruvePaymentMethodView($this->config);
    }

    public function testGetOptions()
    {
        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $this->assertEquals([], $this->methodView->getOptions($context));
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_apruve_widget', $this->methodView->getBlock());
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
        $label = 'short label';

        $this->config->expects(static::once())
            ->method('getShortLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->methodView->getShortLabel());
    }

    public function testGetAdminLabel()
    {
        $label = 'admin label';

        $this->config->expects(static::once())
            ->method('getAdminLabel')
            ->willReturn($label);

        $this->assertEquals($label, $this->methodView->getAdminLabel());
    }

    public function testGetPaymentMethodIdentifier()
    {
        $identifier = 'apruve_1';

        $this->config->expects(static::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn($identifier);

        $this->assertEquals($identifier, $this->methodView->getPaymentMethodIdentifier());
    }
}

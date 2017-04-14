<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Layout\Provider;

use Oro\Bundle\ApruveBundle\Layout\ContextConfigurator\ApruveContextConfigurator;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\Config\Provider\ApruveConfigProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApruveContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var ApruveContextConfigurator
     */
    private $contextConfigurator;

    /**
     * @var ApruveConfigProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configProvider;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configProvider = $this->createMock(ApruveConfigProviderInterface::class);
        $this->config = $this->createMock(ApruveConfigInterface::class);
        $this->context = $this->createMock(ContextInterface::class);

        $this->contextConfigurator = new ApruveContextConfigurator($this->configProvider);
    }

    /**
     * @dataProvider configureContextIfSupportedDataProvider
     *
     * @param string $paymentMethod
     * @param bool $hasPaymentConfig
     */
    public function testConfigureContextIfSupported($paymentMethod, $hasPaymentConfig)
    {
        $this->context
            ->method('has')
            ->with('workflowName')
            ->willReturn(true);

        $this->context
            ->expects(static::at(1))
            ->method('get')
            ->with('workflowName')
            ->willReturn('b2b_flow_checkout');

        $this->context
            ->expects(static::at(2))
            ->method('get')
            ->with('workflowStepName')
            ->willReturn('order_review');

        $resolver = $this->createMock(OptionsResolver::class);
        $this->context
            ->method('getResolver')
            ->willReturn($resolver);

        $resolver
            ->method('setDefined')
            ->with(['is_apruve'])
            ->willReturnSelf();

        $resolver
            ->method('setAllowedTypes')
            ->with(['is_apruve' => ['boolean']]);

        $this->configProvider
            ->method('hasPaymentConfig')
            ->with($paymentMethod)
            ->willReturn($hasPaymentConfig);

        $checkout = $this->createMock(Checkout::class);
        $checkout
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $contextData = $this->createMock(ContextDataCollection::class);
        $contextData
            ->method('get')
            ->with('checkout')
            ->willReturn($checkout);
        $this->context
            ->method('data')
            ->willReturn($contextData);

        $this->context
            ->method('set')
            ->with('is_apruve', $hasPaymentConfig)
            ->willReturn($resolver);

        $this->contextConfigurator->configureContext($this->context);
    }

    /**
     * @return array
     */
    public function configureContextIfSupportedDataProvider()
    {
        return [
            ['apruve_1', true],
            ['another_payment_method', false],
        ];
    }

    /**
     * @dataProvider configureContextIfNotSupportedDataProvider
     *
     * @param bool $hasWorkflowName
     * @param string $workflowName
     * @param string $workflowStepName
     */
    public function testConfigureContextIfNotSupported($hasWorkflowName, $workflowName, $workflowStepName)
    {
        $this->context
            ->method('has')
            ->with('workflowName')
            ->willReturn($hasWorkflowName);

        $this->context
            ->method('get')
            ->willReturnMap([
                ['workflowName', $workflowName],
                ['workflowStepName', $workflowStepName],
            ]);

        $this->context
            ->expects(static::never())
            ->method('getResolver');

        $this->contextConfigurator->configureContext($this->context);
    }

    /**
     * @return array
     */
    public function configureContextIfNotSupportedDataProvider()
    {
        return [
            [false, 'b2b_flow_checkout', 'order_review'],
            [true, 'another_workflow', 'order_review'],
            [true, 'b2b_flow_checkout', 'another_step'],
        ];
    }
}

<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Provider\SupportedCurrenciesProviderInterface;
use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\Factory\ApruvePaymentMethodFactory;
use Oro\Bundle\ApruveBundle\Method\Factory\ApruvePaymentMethodFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\Executor\PaymentActionExecutor;

class ApruvePaymentMethodFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentActionExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentActionExecutor;

    /**
     * @var SupportedCurrenciesProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $supportedCurrenciesProvider;

    /**
     * @var ApruvePaymentMethodFactoryInterface
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->supportedCurrenciesProvider = $this->createMock(SupportedCurrenciesProviderInterface::class);
        $this->paymentActionExecutor = $this->createMock(PaymentActionExecutor::class);
        $this->factory = new ApruvePaymentMethodFactory(
            $this->paymentActionExecutor,
            $this->supportedCurrenciesProvider
        );
    }

    public function testCreate()
    {
        /** @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(ApruveConfigInterface::class);

        $paymentMethod = new ApruvePaymentMethod(
            $config,
            $this->supportedCurrenciesProvider,
            $this->paymentActionExecutor
        );

        static::assertEquals($paymentMethod, $this->factory->create($config));
    }
}

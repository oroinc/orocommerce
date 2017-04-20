<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder\Order;

use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Order\ApruveOrderBuilderFactory;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Provider\ShippingAmountProviderInterface;
use Oro\Bundle\ApruveBundle\Provider\TaxAmountProviderInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class ApruveOrderBuilderFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContext;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var ApruveLineItemBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveLineItemBuilderFactory;

    /**
     * @var ShippingAmountProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingAmountProvider;

    /**
     * @var TaxAmountProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $taxAmountProvider;

    /**
     * @var ApruveOrderBuilderFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->config = $this->createMock(ApruveConfigInterface::class);
        $this->apruveLineItemBuilderFactory = $this->createMock(ApruveLineItemBuilderFactoryInterface::class);
        $this->shippingAmountProvider = $this->createMock(ShippingAmountProviderInterface::class);
        $this->taxAmountProvider = $this->createMock(TaxAmountProviderInterface::class);

        $this->factory = new ApruveOrderBuilderFactory(
            $this->apruveLineItemBuilderFactory,
            $this->shippingAmountProvider,
            $this->taxAmountProvider
        );
    }

    public function testCreate()
    {
        $actual = $this->factory->create($this->paymentContext, $this->config);
        $expected = new ApruveOrderBuilder(
            $this->paymentContext,
            $this->config,
            $this->apruveLineItemBuilderFactory,
            $this->shippingAmountProvider,
            $this->taxAmountProvider
        );

        static::assertEquals($expected, $actual);
    }
}

<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Builder\ApruveOrderBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Factory\ApruveLineItemBuilderFactoryInterface;
use Oro\Bundle\ApruveBundle\Apruve\Builder\Factory\ApruveOrderBuilderFactory;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
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
     * @var ApruveOrderBuilderFactory
     */
    private $factory;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->config = $this->createMock(ApruveConfigInterface::class);
        $this->apruveLineItemBuilderFactory = $this->createMock(ApruveLineItemBuilderFactoryInterface::class);

        $this->factory = new ApruveOrderBuilderFactory($this->apruveLineItemBuilderFactory);
    }

    public function testCreate()
    {
        $actual = $this->factory->create($this->paymentContext, $this->config);
        $expected = new ApruveOrderBuilder($this->paymentContext, $this->config, $this->apruveLineItemBuilderFactory);

        static::assertEquals($expected, $actual);
    }
}

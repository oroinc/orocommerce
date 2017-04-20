<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Builder\LineItem;

use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilder;
use Oro\Bundle\ApruveBundle\Apruve\Builder\LineItem\ApruveLineItemBuilderFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Symfony\Component\Routing\RouterInterface;

class ApruveLineItemBuilderFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveLineItemBuilderFactory
     */
    private $factory;

    /**
     * @var PaymentLineItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentLineItem;

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;


    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->paymentLineItem = $this->createMock(PaymentLineItemInterface::class);
        $this->factory = new ApruveLineItemBuilderFactory($this->router);
    }

    public function testCreate()
    {
        $actual = $this->factory->create($this->paymentLineItem);
        $expected = new ApruveLineItemBuilder($this->paymentLineItem, $this->router);

        static::assertEquals($expected, $actual);
    }
}

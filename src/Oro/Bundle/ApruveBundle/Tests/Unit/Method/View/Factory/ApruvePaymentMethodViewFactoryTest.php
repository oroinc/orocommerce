<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\View\Factory;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\View\Factory\ApruvePaymentMethodViewFactory;
use Oro\Bundle\ApruveBundle\Method\View\Factory\ApruvePaymentMethodViewFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\View\ApruvePaymentMethodView;

class ApruvePaymentMethodViewFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruvePaymentMethodViewFactoryInterface
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->factory = new ApruvePaymentMethodViewFactory();
    }

    public function testCreate()
    {
        /** @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(ApruveConfigInterface::class);

        $method = new ApruvePaymentMethodView($config);

        static::assertEquals($method, $this->factory->create($config));
    }
}

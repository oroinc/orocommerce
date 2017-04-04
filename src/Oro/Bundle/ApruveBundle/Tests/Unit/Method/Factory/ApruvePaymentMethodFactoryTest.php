<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\Factory\ApruvePaymentMethodFactory;
use Oro\Bundle\ApruveBundle\Method\Factory\ApruvePaymentMethodFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\ApruvePaymentMethod;

class ApruvePaymentMethodFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruvePaymentMethodFactoryInterface
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->factory = new ApruvePaymentMethodFactory();
    }

    public function testCreate()
    {
        /** @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject $config */
        $config = $this->createMock(ApruveConfigInterface::class);

        $paymentMethod = new ApruvePaymentMethod($config);

        static::assertEquals($paymentMethod, $this->factory->create($config));
    }
}

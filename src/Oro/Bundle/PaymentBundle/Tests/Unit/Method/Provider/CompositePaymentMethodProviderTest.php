<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\CompositePaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;

class CompositePaymentMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    const IDENTIFIER1 = 'test1';
    const IDENTIFIER2 = 'test2';
    const WRONG_IDENTIFIER = 'wrong';

    /**
     * @var CompositePaymentMethodProvider
     */
    protected $registry;

    /**
     * @var PaymentMethodProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $methodProvider;

    /**
     * @var array
     */
    protected $methods;

    protected function setUp()
    {
        $this->registry = new CompositePaymentMethodProvider();

        $this->methods = [
            self::IDENTIFIER1 => $this->getMethodMock(self::IDENTIFIER1),
            self::IDENTIFIER2 => $this->getMethodMock(self::IDENTIFIER2),
        ];

        $this->methodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->methodProvider->expects(static::any())->method('getPaymentMethods')
            ->will(static::returnValue($this->methods));
        $this->methodProvider->expects(static::any())->method('getPaymentMethod')
            ->willReturnMap([
                [self::IDENTIFIER1, $this->methods[self::IDENTIFIER1]],
                [self::IDENTIFIER2, $this->methods[self::IDENTIFIER2]],
            ]);
        $this->methodProvider->expects(static::any())->method('hasPaymentMethod')
            ->willReturnMap([
                [self::IDENTIFIER1, true],
                [self::IDENTIFIER2, true],
            ]);

        $this->registry->addProvider($this->methodProvider);
    }

    public function testGetPaymentMethods()
    {
        $methods = $this->registry->getPaymentMethods();
        static::assertCount(2, $methods);
        static::assertEquals($this->methods, $methods);
    }

    public function testGetPaymentMethod()
    {
        static::assertEquals($this->methods[self::IDENTIFIER1], $this->registry->getPaymentMethod(self::IDENTIFIER1));
        static::assertEquals($this->methods[self::IDENTIFIER2], $this->registry->getPaymentMethod(self::IDENTIFIER2));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp  /There is no payment method for "\w+" identifier/
     */
    public function testGetPaymentMethodExceptionTriggered()
    {
        $this->registry->getPaymentMethod(self::WRONG_IDENTIFIER);
    }


    public function testHasPaymentMethod()
    {
        static::assertEquals(true, $this->registry->hasPaymentMethod(self::IDENTIFIER1));
        static::assertEquals(true, $this->registry->hasPaymentMethod(self::IDENTIFIER2));
    }

    /**
     * @param string $identifier
     *
     * @return PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMethodMock($identifier)
    {
        $method = $this->createMock(PaymentMethodInterface::class);
        $method->expects(static::any())->method('getIdentifier')->will(static::returnValue($identifier));

        return $method;
    }
}

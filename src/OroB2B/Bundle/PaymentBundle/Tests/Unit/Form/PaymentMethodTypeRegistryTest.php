<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form;

use Symfony\Component\Form\FormTypeInterface;

use OroB2B\Bundle\PaymentBundle\Form\PaymentMethodViewRegistry;

class PaymentMethodTypeRegistryTest extends \PHPUnit_Framework_TestCase
{
    const NAME = 'test_method_type';

    public function testRegistry()
    {
        $testType = $this->getTypeMock(self::NAME);
        $registry = new PaymentMethodViewRegistry();

        $this->assertEmpty($registry->getPaymentMethodTypes());

        $registry->addPaymentMethodType($testType);

        $this->assertCount(1, $registry->getPaymentMethodTypes());
        $this->assertEquals($testType, $registry->getPaymentMethodType(self::NAME));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Tax payment type with name "test_method_type" does not exist
     */
    public function testWrongTypeException()
    {
        $registry = new PaymentMethodViewRegistry();
        $registry->getPaymentMethodType(self::NAME);
    }

    /**
     * @param string $name
     * @return \PHPUnit_Framework_MockObject_MockObject|FormTypeInterface
     */
    protected function getTypeMock($name)
    {
        $type = $this->getMock('Symfony\Component\Form\FormTypeInterface');
        $type->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $type;
    }
}

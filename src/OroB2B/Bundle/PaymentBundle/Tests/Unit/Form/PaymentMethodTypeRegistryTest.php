<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Form;

use OroB2B\Bundle\PaymentBundle\Form\PaymentMethodTypeRegistry;
use OroB2B\Bundle\PaymentBundle\Form\Type\AbstractPaymentMethodType;

class PaymentMethodTypeRegistryTest extends \PHPUnit_Framework_TestCase
{
    const NAME = 'test_method_type';

    public function testRegistry()
    {
        /** @var AbstractPaymentMethodType $testType */
        $testType = $this->getTypeMock(self::NAME);
        $registry = new PaymentMethodTypeRegistry();

        $this->assertEmpty($registry->getPaymentMethodTypes());

        $registry->addPaymentMethodType($testType);

        $this->assertCount(1, $registry->getPaymentMethodTypes());
        $this->assertEquals($testType, $registry->getPaymentMethodType(self::NAME));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Payment method type with name "test_method_type" does not exist
     */
    public function testWrongTypeException()
    {
        $registry = new PaymentMethodTypeRegistry();
        $registry->getPaymentMethodType(self::NAME);
    }

    /**
     * @param string $name
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractPaymentMethodType
     */
    protected function getTypeMock($name)
    {
        $type = $this->getMock('OroB2B\Bundle\PaymentBundle\Form\Type\AbstractPaymentMethodType');
        $type->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $type;
    }
}

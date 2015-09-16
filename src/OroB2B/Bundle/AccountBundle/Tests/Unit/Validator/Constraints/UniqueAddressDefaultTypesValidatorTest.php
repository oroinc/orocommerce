<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;

use OroB2B\Bundle\AccountBundle\Validator\Constraints\UniqueAddressDefaultTypes;
use OroB2B\Bundle\AccountBundle\Validator\Constraints\UniqueAddressDefaultTypesValidator;

class UniqueAddressDefaultTypesValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array or Traversable and ArrayAccess", "boolean" given
     */
    public function testValidateExceptionWhenInvalidArgumentType()
    {
        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMock('Symfony\Component\Validator\Constraint');
        $validator = new UniqueAddressDefaultTypesValidator();
        $validator->validate(false, $constraint);
    }

    public function testValidateExceptionWhenInvalidArgumentElementType()
    {
        $this->setExpectedException(
            'Symfony\Component\Validator\Exception\ValidatorException',
            'Expected argument of type "OroB2B\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress", "array" given'
        );

        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMock('Symfony\Component\Validator\Constraint');
        $validator = new UniqueAddressDefaultTypesValidator();
        $validator->validate([1], $constraint);
    }

    /**
     * @dataProvider validAddressesDataProvider
     * @param array $addresses
     */
    public function testValidateValid(array $addresses)
    {
        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $context->expects($this->never())
            ->method('addViolation');

        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMock('OroB2B\Bundle\AccountBundle\Validator\Constraints\UniqueAddressDefaultTypes');
        $validator = new UniqueAddressDefaultTypesValidator();
        $validator->initialize($context);

        $validator->validate($addresses, $constraint);
    }

    /**
     * @return array
     */
    public function validAddressesDataProvider()
    {
        return [
            'no addresses' => [
                [],
            ],
            'one address without type' => [
                [$this->getDefaultTypedAddressMock([])],
            ],
            'one address with type' => [
                [$this->getDefaultTypedAddressMock(['billing' => 'billing label'])],
            ],
            'many addresses unique types' => [
                [
                    $this->getDefaultTypedAddressMock(['billing' => 'billing label']),
                    $this->getDefaultTypedAddressMock(['shipping' => 'shipping label']),
                    $this->getDefaultTypedAddressMock(['billing_corporate' => 'billing_corporate label']),
                    $this->getDefaultTypedAddressMock([]),
                ],
            ],
            'empty address' => [
                [
                    $this->getDefaultTypedAddressMock(['billing' => 'billing label']),
                    $this->getDefaultTypedAddressMock(['shipping' => 'shipping label']),
                    $this->getDefaultTypedAddressMock([], true),
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidAddressesDataProvider
     * @param array  $addresses
     * @param string $types
     */
    public function testValidateInvalid($addresses, $types)
    {
        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContextInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('addViolation')
            ->with('Several addresses have the same default type {{ types }}.', ['{{ types }}' => $types]);

        /** @var UniqueAddressDefaultTypes|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMock('OroB2B\Bundle\AccountBundle\Validator\Constraints\UniqueAddressDefaultTypes');
        $validator = new UniqueAddressDefaultTypesValidator();
        $validator->initialize($context);

        $validator->validate($addresses, $constraint);
    }

    /**
     * @return array
     */
    public function invalidAddressesDataProvider()
    {
        return [
            'several addresses with one same type' => [
                [
                    $this->getDefaultTypedAddressMock(['billing' => 'billing label']),
                    $this->getDefaultTypedAddressMock(['billing' => 'billing label', 'shipping' => 'shipping label']),
                ],
                '"billing label"',
            ],
            'several addresses with two same types' => [
                [
                    $this->getDefaultTypedAddressMock(['billing' => 'billing label']),
                    $this->getDefaultTypedAddressMock(['shipping' => 'shipping label']),
                    $this->getDefaultTypedAddressMock(['billing' => 'billing label', 'shipping' => 'shipping label']),
                ],
                '"billing label", "shipping label"',
            ],
        ];
    }

    /**
     * Get address mock.
     *
     * @param array $addressTypes
     * @param bool  $isEmpty
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDefaultTypedAddressMock(array $addressTypes, $isEmpty = false)
    {
        $address = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress')
            ->disableOriginalConstructor()
            ->setMethods(['getDefaults', 'isEmpty'])
            ->getMockForAbstractClass();

        $addressTypeEntities = [];
        foreach ($addressTypes as $name => $label) {
            $addressType = new AddressType($name);
            $addressType->setLabel($label);
            $addressTypeEntities[] = $addressType;
        }

        $address->expects($this->any())
            ->method('getDefaults')
            ->will($this->returnValue($addressTypeEntities));

        $address->expects($this->once())
            ->method('isEmpty')
            ->will($this->returnValue($isEmpty));

        return $address;
    }
}

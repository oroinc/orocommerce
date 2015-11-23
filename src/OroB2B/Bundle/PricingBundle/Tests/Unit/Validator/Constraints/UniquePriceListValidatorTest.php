<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContext;

use OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniquePriceList;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniquePriceListValidator;

class UniquePriceListValidatorTest extends \PHPUnit_Framework_TestCase
{
    use ConfigsGeneratorTrait;

    public function testValidationOnValid()
    {
        $constraint = new UniquePriceList();
        $validator = new UniquePriceListValidator();

        $validator->initialize($this->getContext());
        $validator->validate($this->createConfigs(2), $constraint);
    }

    public function testValidationOnInvalid()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addViolation', 'atPath'])
            ->getMock()
        ;

        $builder->expects($this->once())
            ->method('atPath')
            ->with('[2].priceList')
            ->willReturn($builder)
        ;

        $context = $this->getContext();

        $constraint = new UniquePriceList();
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($this->equalTo($constraint->message), [])
            ->will($this->returnValue($builder))
        ;

        $validator = new UniquePriceListValidator();
        $validator->initialize($context);

        $value = array_merge($this->createConfigs(2), $this->createConfigs(1));
        $validator->validate($value, $constraint);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidationOnInvalidTypeInCollection()
    {
        $validator = new UniquePriceListValidator();
        $validator->initialize($this->getContext());

        $value = [new \stdClass()];
        $validator->validate($value, new UniquePriceList());
    }


    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context
     */
    protected function getContext()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->setMethods(['buildViolation'])
            ->getMock();
    }
}

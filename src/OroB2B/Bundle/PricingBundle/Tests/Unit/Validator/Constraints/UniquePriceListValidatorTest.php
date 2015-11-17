<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigBag;
use OroB2B\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniquePriceList;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\UniquePriceListValidator;

use Symfony\Component\Validator\Context\ExecutionContext;
use Doctrine\Common\Collections\ArrayCollection;

class UniquePriceListValidatorTest extends \PHPUnit_Framework_TestCase
{
    use ConfigsGeneratorTrait;

    public function testValidationOnValid()
    {
        $bag = new PriceListConfigBag();
        $bag->setConfigs(new ArrayCollection($this->createConfigs(2)));

        $constraint = new UniquePriceList();
        $validator = new UniquePriceListValidator();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context */
        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $validator->initialize($context);
        $validator->validate($bag, $constraint);
    }

    public function testValidationOnInvalid()
    {
        $bag = new PriceListConfigBag();
        $configs = array_merge($this->createConfigs(2), $this->createConfigs(1));
        $bag->setConfigs(new ArrayCollection($configs));
        $constraint = new UniquePriceList();

        $builder = $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addViolation'])
            ->getMock()
        ;

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context */
        $context = $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->setMethods(['buildViolation'])
            ->getMock()
        ;

        $context->expects($this->once())
            ->method('buildViolation')
            ->with($this->equalTo($constraint->message), ['priceLists' => 'Price List 1'])
            ->will($this->returnValue($builder))
        ;

        $validator = new UniquePriceListValidator();
        $validator->initialize($context);

        $validator->validate($bag, $constraint);
    }
}

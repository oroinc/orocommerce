<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\NotEmptyPriceListsValidator;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\NotEmptyPriceLists;

class NotEmptyPriceListsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var NotEmptyPriceLists
     */
    protected $constraint;

    /**
     * @var NotEmptyPriceListsValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->constraint = new NotEmptyPriceLists();
        $this->validator = new NotEmptyPriceListsValidator();
    }

    public function testValidData()
    {
        $this->context->expects($this->never())
            ->method('buildViolation');

        $this->validator->initialize($this->context);
        $this->validator->validate([new PriceListConfig()], $this->constraint);
    }

    public function testInvalidData()
    {
        $violation = $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $violation->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($violation);

        $this->validator->initialize($this->context);
        $this->validator->validate([], $this->constraint);
    }
}

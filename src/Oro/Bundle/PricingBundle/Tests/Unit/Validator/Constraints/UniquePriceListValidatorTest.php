<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\Tests\Unit\SystemConfig\ConfigsGeneratorTrait;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniquePriceList;
use Oro\Bundle\PricingBundle\Validator\Constraints\UniquePriceListValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

class UniquePriceListValidatorTest extends \PHPUnit\Framework\TestCase
{
    use ConfigsGeneratorTrait;

    /** @var  UniquePriceList */
    protected $constraint;

    /** @var  UniquePriceListValidator */
    protected $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->constraint = new UniquePriceList();
        $this->validator = new UniquePriceListValidator();
    }

    public function testValidationOnValid()
    {
        $this->validator->initialize($this->getContextMock());
        $this->validator->validate($this->createConfigs(2), $this->constraint);
    }

    public function testValidationOnInvalid()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('atPath')
            ->with('[2].priceList')
            ->willReturn($builder)
        ;

        $context = $this->getContextMock();
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($this->equalTo($this->constraint->message), [])
            ->will($this->returnValue($builder))
        ;

        $this->validator->initialize($context);

        $value = array_merge($this->createConfigs(2), $this->createConfigs(1));
        $this->validator->validate($value, $this->constraint);
    }

    public function testValidationOnInvalidArrayValue()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('atPath')
            ->with('[2][priceList]')
            ->willReturn($builder)
        ;

        $context = $this->getContextMock();
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($this->equalTo($this->constraint->message), [])
            ->will($this->returnValue($builder))
        ;

        $this->validator->initialize($context);

        $value = array_map(function ($item) {
            /** @var PriceListConfig $item */
            return ['priceList' => $item->getPriceList(), 'sortOrder' => $item->getSortOrder()];
        }, array_merge($this->createConfigs(2), $this->createConfigs(1)));
        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExecutionContext $context
     */
    protected function getContextMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->setMethods(['buildViolation'])
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getBuilderMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addViolation', 'atPath'])
            ->getMock()
            ;
    }
}

<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroup;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledTypeConfigsValidationGroupValidator;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptionsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class EnabledTypeConfigsValidationGroupValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var UniqueProductUnitShippingOptions */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    protected $context;

    /** @var UniqueProductUnitShippingOptionsValidator */
    protected $validator;

    protected function setUp(): void
    {
        $this->constraint = new EnabledTypeConfigsValidationGroup();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new EnabledTypeConfigsValidationGroupValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'oro_shipping_enabled_type_config_validation_group_validator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetDefaultOption()
    {
        $this->assertNull($this->constraint->getDefaultOption());
    }

    public function testValidateWithoutDuplications()
    {
        $this->context->expects($this->never())->method('buildViolation');

        $data = new ArrayCollection([
            (new ShippingMethodTypeConfig())->setEnabled(false),
            (new ShippingMethodTypeConfig())->setEnabled(true),
        ]);

        $this->validator->validate($data, $this->constraint);
    }

    public function testValidateWithDuplications()
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($builder);

        $builder->expects($this->exactly(2))
            ->method('setParameter')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('setPlural')
            ->with($this->constraint->min)
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('atPath')
            ->with('configurations')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('addViolation');

        $data = new ArrayCollection([
            (new ShippingMethodTypeConfig())->setEnabled(false),
            (new ShippingMethodTypeConfig())->setEnabled(false),
        ]);

        $this->validator->validate($data, $this->constraint);
    }

    public function testUnexpectedValue()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "array or Traversable and Countable", "string" given'
        );

        $this->validator->validate('test', $this->constraint);
    }

    public function testUnexpectedItem()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig",'
            . ' "stdClass" given'
        );
        $data = new ArrayCollection([new \stdClass()]);
        $this->validator->validate($data, $this->constraint);
    }
}

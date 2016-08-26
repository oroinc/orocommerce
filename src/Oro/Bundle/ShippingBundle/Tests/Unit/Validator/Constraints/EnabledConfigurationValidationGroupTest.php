<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

use Oro\Bundle\ShippingBundle\Tests\Unit\Entity\Stub\CustomShippingRuleConfiguration;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledConfigurationValidationGroup;
use Oro\Bundle\ShippingBundle\Validator\Constraints\EnabledConfigurationValidationGroupValidator;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptions;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptionsValidator;

class EnabledConfigurationValidationGroupTest extends \PHPUnit_Framework_TestCase
{
    /** @var UniqueProductUnitShippingOptions */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface */
    protected $context;

    /** @var UniqueProductUnitShippingOptionsValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new EnabledConfigurationValidationGroup();
        $this->context = $this->getMock(ExecutionContextInterface::class);

        $this->validator = new EnabledConfigurationValidationGroupValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'orob2b_shipping_enabled_configuration_validation_group_validator',
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
            (new CustomShippingRuleConfiguration())->setEnabled(false),
            (new CustomShippingRuleConfiguration())->setEnabled(true),
        ]);

        $this->validator->validate($data, $this->constraint);
    }

    public function testValidateWithDuplications()
    {
        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message, ['{{ limit }}' => $this->constraint->min])
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('atPath')
            ->with('configurations')
            ->willReturn($builder);

        $builder->expects($this->once())
            ->method('addViolation');

        $data = new ArrayCollection([
            (new CustomShippingRuleConfiguration())->setEnabled(false),
            (new CustomShippingRuleConfiguration())->setEnabled(false),
        ]);

        $this->validator->validate($data, $this->constraint);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "array or Traversable and Countable", "string" given
     */
    public function testUnexpectedValue()
    {
        $this->validator->validate('test', $this->constraint);
    }

    public function testUnexpectedItem()
    {
        $this->setExpectedException(
            '\Symfony\Component\Validator\Exception\UnexpectedTypeException',
            'Expected argument of type "Oro\Bundle\ShippingBundle\Model\ShippingRuleConfiguration", "stdClass" given'
        );
        $data = new ArrayCollection([new \stdClass()]);
        $this->validator->validate($data, $this->constraint);
    }
}

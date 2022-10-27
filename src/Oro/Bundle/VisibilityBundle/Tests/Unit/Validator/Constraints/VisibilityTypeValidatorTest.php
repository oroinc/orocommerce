<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Validator\Constraints\VisibilityType;
use Oro\Bundle\VisibilityBundle\Validator\Constraints\VisibilityTypeValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class VisibilityTypeValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        return new VisibilityTypeValidator();
    }

    /**
     * @return VisibilityType
     */
    private function getConstraint()
    {
        return new VisibilityType();
    }

    public function testValidateOnInvalidConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('', $this->createMock(Constraint::class));
    }

    public function testValidateOnNullValue()
    {
        $this->validator->validate(null, $this->getConstraint());

        $this->assertNoViolation();
    }

    public function testValidateOnInvalidData()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type '
            . '"Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface", "stdClass" given'
        );
        $this->validator->validate(new \stdClass(), $this->getConstraint());
    }

    public function testValidateOnEmptyTarget()
    {
        $entity = new ProductVisibility();
        $this->validator->validate($entity, $this->getConstraint());

        $this->assertNoViolation();
    }

    public function testValidateOnCorrectData()
    {
        $entity = new ProductVisibility();
        $entity->setProduct(new Product());
        $entity->setVisibility('visible');

        $this->validator->validate($entity, $this->getConstraint());

        $this->assertNoViolation();
    }

    public function testValidateOnWrongValidationType()
    {
        $constraint = $this->getConstraint();

        $entity = new ProductVisibility();
        $entity->setProduct(new Product());
        $entity->setVisibility('wrong');

        $this->validator->validate($entity, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.visibility')
            ->setParameter('{{ available_types }}', 'category, config, hidden, visible')
            ->assertRaised();
    }
}

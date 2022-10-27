<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\VisibilityBundle\Validator\Constraints\VisibilityChangeSet;
use Oro\Bundle\VisibilityBundle\Validator\Constraints\VisibilityChangeSetValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class VisibilityChangeSetValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @return VisibilityChangeSetValidator
     */
    protected function createValidator()
    {
        return new VisibilityChangeSetValidator();
    }

    /**
     * @return VisibilityChangeSet
     */
    private function getConstraint()
    {
        return new VisibilityChangeSet(['entityClass' => Customer::class]);
    }

    public function testValidateNullValue()
    {
        $this->validator->validate(null, $this->getConstraint());

        $this->assertNoViolation();
    }

    public function testValidateEmptyArrayCollection()
    {
        $this->validator->validate(new ArrayCollection(), $this->getConstraint());

        $this->assertNoViolation();
    }

    public function testValidateAnotherEntity()
    {
        $value = new ArrayCollection();
        $value->add(['data' => ['visibility' => 'visible'], 'entity' => new \stdClass()]);

        $constraint = $this->getConstraint();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidData()
    {
        $value = new ArrayCollection();
        $value->add(['data' => ['visibility' => 'visible'], 'entity' => new Customer()]);

        $this->validator->validate($value, $this->getConstraint());

        $this->assertNoViolation();
    }

    public function testValidateNotCollection()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), $this->getConstraint());
    }
}

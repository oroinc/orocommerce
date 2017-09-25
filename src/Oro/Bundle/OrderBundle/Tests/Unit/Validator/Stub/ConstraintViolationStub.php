<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Stub;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;

class ConstraintViolationStub extends ConstraintViolation
{
    /**
     * @var Constraint
     */
    private $constraint;

    /**
     * @var mixed
     */
    private $invalidValue;

    /**
     * ConstraintViolationStub constructor.
     * @param Constraint $constraint
     * @param mixed $invalidValue
     */
    public function __construct(Constraint $constraint, $invalidValue)
    {
        $this->constraint = $constraint;
        $this->invalidValue = $invalidValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getInvalidValue()
    {
        return $this->invalidValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getConstraint()
    {
        return $this->constraint;
    }
}

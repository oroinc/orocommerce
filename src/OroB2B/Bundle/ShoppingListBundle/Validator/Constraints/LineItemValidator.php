<?php

namespace OroB2B\Bundle\ShoppingListBundle\Validator\Constraints;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class LineItemValidator extends ConstraintValidator
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \OroB2B\Bundle\ShoppingListBundle\Entity\LineItem $value
     * @param Constraint|LineItem $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($this->registry->getRepository('OroB2BShoppingListBundle:LineItem')->findDuplicate($value)) {
            $this->context->addViolation($constraint->message);
        }
    }
}

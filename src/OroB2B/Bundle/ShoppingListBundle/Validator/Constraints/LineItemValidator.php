<?php

namespace Oro\Bundle\ShoppingListBundle\Validator\Constraints;

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
     * @param \Oro\Bundle\ShoppingListBundle\Entity\LineItem $value
     * @param Constraint|LineItem $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($this->registry->getRepository('OroShoppingListBundle:LineItem')->findDuplicate($value)) {
            $this->context->addViolation($constraint->message);
        }
    }
}

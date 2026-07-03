<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Enables a disabled DraftSession ORM filter around UniqueEntity validation
 * so that draft copies of an Order do not cause false-positive uniqueness violations.
 */
class OrderUniqueEntityValidator extends ConstraintValidator
{
    public function __construct(
        private ConstraintValidatorInterface $inner,
        private DraftSessionOrmFilterManager $draftFilterManager,
    ) {
    }

    #[\Override]
    public function initialize(ExecutionContextInterface $context): void
    {
        parent::initialize($context);
        $this->inner->initialize($context);
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof Order) {
            $this->inner->validate($value, $constraint);

            return;
        }

        $wasEnabled = $this->draftFilterManager->isEnabled();
        if (!$wasEnabled) {
            $this->draftFilterManager->enable();
        }

        try {
            $this->inner->validate($value, $constraint);
        } finally {
            if (!$wasEnabled) {
                $this->draftFilterManager->disable();
            }
        }
    }
}

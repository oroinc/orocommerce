<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if the default scopes are used.
 */
class TextContentVariantScopeValidator extends ConstraintValidator
{
    public function __construct(private ScopeManager $scopeManager)
    {
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$value instanceof Scope) {
            return;
        }

        $defaultScope = $this->scopeManager->findDefaultScope();
        if ($defaultScope->getRowHash() !== $value->getRowHash()) {
            return;
        }

        $this->context->addViolation($constraint->message);
    }
}

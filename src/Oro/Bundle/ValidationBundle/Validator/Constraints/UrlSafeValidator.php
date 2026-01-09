<?php

namespace Oro\Bundle\ValidationBundle\Validator\Constraints;

use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;

/**
 * Validator to check that Url is safe to be used in Oro app.
 */
class UrlSafeValidator extends RegexValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UrlSafe) {
            return;
        }

        $delimiter = SluggableUrlGenerator::CONTEXT_DELIMITER;
        if ($value === $delimiter
            || str_starts_with($value, $delimiter . '/')
            || str_ends_with($value, '/' . $delimiter)
            || str_contains($value, '/' . $delimiter . '/')
        ) {
            $this->context->buildViolation($constraint->delimiterMessage)
                ->addViolation();

            return;
        }

        parent::validate($value, $constraint);
    }
}

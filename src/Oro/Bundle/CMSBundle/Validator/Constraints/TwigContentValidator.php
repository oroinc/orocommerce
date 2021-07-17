<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Twig\Environment;
use Twig\Error\Error;

/**
 * This validator checks that WYSIWYG field does not have errors in the twig content.
 */
class TwigContentValidator extends ConstraintValidator
{
    /** @var Environment */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     *
     * @param TwigContent $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        try {
            $templateWrapper = $this->twig->createTemplate((string) $value);
            $templateWrapper->render();
        } catch (Error $e) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}

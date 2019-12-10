<?php

namespace Oro\Bundle\CMSBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Source;

/**
 * This validator checks that WYSIWYG field does not have errors in the twig content.
 */
class TwigContentValidator extends ConstraintValidator
{
    /** @var Environment */
    private $twig;

    /**
     * @param Environment $twig
     */
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
            $this->twig->tokenize(new Source((string) $value, 'content'));
        } catch (SyntaxError $e) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}

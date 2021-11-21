<?php

namespace Oro\Bundle\RedirectBundle\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates that slug prototype is url safe.
 */
class UrlSafeSlugPrototypeValidator extends ConstraintValidator
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($slugPrototype, Constraint $constraint)
    {
        if (!$constraint instanceof UrlSafeSlugPrototype) {
            throw new UnexpectedTypeException($constraint, UrlSafeSlugPrototype::class);
        }

        /** @var LocalizedFallbackValue $slugPrototype */
        if (null === $slugPrototype) {
            return;
        }

        $violations = $this->validator->validate(
            $slugPrototype->getString(),
            new UrlSafe(['allowSlashes' => $constraint->allowSlashes])
        );

        if ($violations->count()) {
            $violation = $violations->get(0);
            $this->context->buildViolation($violation->getMessage())
                ->addViolation();
        }
    }
}

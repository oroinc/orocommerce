<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\OrderBundle\Entity\OrderAddress as OrderAddressEntity;

class OrderAddressValidator extends ConstraintValidator
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var OrderAddressEntity $value */
        if (!$constraint instanceof ConstraintByValidationGroups) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\ConstraintByValidationGroups');
        }
        if ($value && !$value->getAccountAddress() && !$value->getAccountUserAddress()) {
            /** @var ConstraintViolationList $violationList */
            $violationList = $this->validator->validate($value, null, $constraint->getValidationGroups());
            /** @var ConstraintViolation $violation */
            foreach ($violationList as $violation) {
                $this->context->buildViolation($violation->getMessage(), $violation->getParameters())
                    ->atPath($violation->getPropertyPath())
                    ->addViolation();
            }
        }
    }
}

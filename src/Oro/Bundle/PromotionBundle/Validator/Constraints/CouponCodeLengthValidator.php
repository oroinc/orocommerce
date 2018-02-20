<?php

namespace Oro\Bundle\PromotionBundle\Validator\Constraints;

use Oro\Bundle\PromotionBundle\CouponGeneration\Options\CodeGenerationOptions;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator used to check coupon length before insert it to DB
 */
class CouponCodeLengthValidator extends ConstraintValidator
{
    const ALIAS = 'oro_promotion_coupon_code_length';

    /**
     * @inheritDoc
     * @param CodeGenerationOptions $entity
     * @throws UnexpectedTypeException
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof CodeGenerationOptions) {
            throw new UnexpectedTypeException($entity, CodeGenerationOptions::class);
        }

        $codeLength = $entity->getCodeLength();
        $prefixLength = mb_strlen($entity->getCodePrefix());
        $suffixLength = mb_strlen($entity->getCodeSuffix());

        $numberOfDashes = 0;
        if ($entity->getDashesSequence() > 0) {
            $numberOfDashes = floor($codeLength / $entity->getDashesSequence());
        }

        $fullCodeLength = $codeLength + $prefixLength + $suffixLength + $numberOfDashes;

        if ($fullCodeLength > Coupon::MAX_COUPON_CODE_LENGTH) {
            $this->context->addViolation($constraint->message, [
                '{{ actualLength }}' => $fullCodeLength,
                '{{ maxAllowedLength }}' => Coupon::MAX_COUPON_CODE_LENGTH,
            ]);
        }
    }
}

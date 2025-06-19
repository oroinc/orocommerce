<?php

namespace Oro\Bundle\PromotionBundle\Validator\Constraints;

use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validator for {@see ConfigCouponCaseInsensitiveOption} constraint.
 * Checks whether enabling case-insensitive coupon code handling would result in duplicates
 * for the specified organization.
 */
class ConfigCouponCaseInsensitiveOptionValidator extends ConstraintValidator
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ConfigCouponCaseInsensitiveOption) {
            throw new UnexpectedTypeException($constraint, ConfigCouponCaseInsensitiveOption::class);
        }

        if ($value) {
            $this->checkDuplicatedCoupons($constraint);
        }
    }

    private function checkDuplicatedCoupons(ConfigCouponCaseInsensitiveOption $constraint): void
    {
        $hasDuplicated = $this->hasDuplicatesInInsensitiveMode();
        if (!$hasDuplicated) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->addViolation();
    }

    private function hasDuplicatesInInsensitiveMode(): bool
    {
        /** @var CouponRepository $repository */
        $repository = $this->managerRegistry->getRepository(Coupon::class);

        return $repository->hasDuplicatesInCaseInsensitiveMode();
    }
}

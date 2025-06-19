<?php

namespace Oro\Bundle\PromotionBundle\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PromotionBundle\DependencyInjection\Configuration;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates a coupon code to ensure it is unique, taking into account case-insensitivity
 * if the feature is enabled in the system configuration.
 * This validator checks for duplicates across specified organization when case-insensitive
 * coupon validation is enabled. If duplicates are found, a validation violation is added.
 */
class UniqueCaseInsensitiveCouponCodeValidator extends ConstraintValidator
{
    public function __construct(private ManagerRegistry $managerRegistry, private ConfigManager $configManager)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueCaseInsensitiveCouponCode) {
            throw new UnexpectedTypeException($constraint, UniqueCaseInsensitiveCouponCode::class);
        }

        if (!$value instanceof Coupon) {
            throw new UnexpectedTypeException($value, Coupon::class);
        }

        if (!$value->getCode()) {
            return;
        }

        $key = Configuration::getConfigKey(Configuration::CASE_INSENSITIVE_COUPON_SEARCH);
        $caseInsensitiveCouponCodesEnabled = $this->configManager->get($key, false, false, $value->getOrganization());
        if (!$caseInsensitiveCouponCodesEnabled) {
            return;
        }

        if ($this->hasDuplicatesInInsensitiveMode($value->getCode())) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ code }}', $value->getCode())
                ->addViolation();
        }
    }

    private function hasDuplicatesInInsensitiveMode(string $couponCode): bool
    {
        /** @var CouponRepository $repository */
        $repository = $this->managerRegistry->getRepository(Coupon::class);

        return (bool) $repository->getCouponByCode($couponCode, true);
    }
}

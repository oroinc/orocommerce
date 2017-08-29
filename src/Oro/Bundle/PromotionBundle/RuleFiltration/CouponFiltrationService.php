<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class CouponFiltrationService implements RuleFiltrationServiceInterface
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @param RuleFiltrationServiceInterface $filtrationService
     */
    public function __construct(RuleFiltrationServiceInterface $filtrationService)
    {
        $this->filtrationService = $filtrationService;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        $ruleOwners = $this->getFilteredPromotions($ruleOwners, $context);

        return $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context);
    }

    /**
     * @param array $ruleOwners
     * @param array $context
     * @return array
     */
    private function getFilteredPromotions(array $ruleOwners, array $context)
    {
        $appliedCoupons = new ArrayCollection();
        if (array_key_exists(ContextDataConverterInterface::APPLIED_COUPONS, $context)) {
            $appliedCoupons = $context[ContextDataConverterInterface::APPLIED_COUPONS];
        }

        return array_values(array_filter($ruleOwners, function ($ruleOwner) use ($appliedCoupons) {
            if (!$ruleOwner instanceof PromotionDataInterface) {
                return false;
            }

            if (!$ruleOwner->isUseCoupons()) {
                return true;
            } elseif ($appliedCoupons->isEmpty()) {
                return false;
            }

            $matchedCoupons = $this->getMatchingCoupons($ruleOwner->getCoupons(), $appliedCoupons)
                ->filter(function (Coupon $coupon) use ($ruleOwner) {
                    return $coupon->getPromotion() && $coupon->getPromotion()->getId() === $ruleOwner->getId();
                });
            
            return !$matchedCoupons->isEmpty();
        }));
    }

    /**
     * @param Selectable $coupons
     * @param Collection $appliedCoupons
     * @return Collection
     */
    private function getMatchingCoupons(Selectable $coupons, Collection $appliedCoupons): Collection
    {
        $appliedCouponCodes = $appliedCoupons->map(
            function (Coupon $coupon) {
                return $coupon->getCode();
            }
        )->toArray();

        $criteria = Criteria::create();
        $criteria->where(
            Criteria::expr()->in('code', $appliedCouponCodes)
        );


        return $coupons->matching($criteria);
    }
}

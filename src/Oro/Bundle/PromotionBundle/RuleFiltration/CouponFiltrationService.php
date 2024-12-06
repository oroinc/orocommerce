<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Entity\Repository\CouponRepository;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * Filters out promotions which use coupons and have no intersection with coupons
 * of a source entity from the context.
 */
class CouponFiltrationService extends AbstractSkippableFiltrationService
{
    private array $matchedPromotionIds = [];

    public function __construct(
        private RuleFiltrationServiceInterface $baseFiltrationService,
        private ManagerRegistry $doctrine
    ) {
    }

    /**
     * {@inheritDoc}
     */
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredRuleOwners = $this->doFilterRuleOwners($ruleOwners, $this->getAppliedCouponIds($context));

        return $this->baseFiltrationService->getFilteredRuleOwners($filteredRuleOwners, $context);
    }

    private function doFilterRuleOwners(array $ruleOwners, array $appliedCouponIds): array
    {
        $filteredRuleOwners = [];
        $ruleOwnersWithCoupons = [];
        foreach ($ruleOwners as $ruleOwner) {
            if (!$ruleOwner instanceof PromotionDataInterface) {
                continue;
            }
            if (!$ruleOwner->isUseCoupons()) {
                $filteredRuleOwners[] = $ruleOwner;
            } elseif ($appliedCouponIds) {
                $ruleOwnersWithCoupons[] = $ruleOwner;
            }
        }

        if ($ruleOwnersWithCoupons) {
            $filteredRuleOwners = array_merge(
                $filteredRuleOwners,
                $this->filterRuleOwnersWithCoupons($ruleOwnersWithCoupons, $appliedCouponIds)
            );
        }

        return $filteredRuleOwners;
    }

    private function getAppliedCouponIds(array $context): array
    {
        $appliedCoupons = $context[ContextDataConverterInterface::APPLIED_COUPONS] ?? [];
        if (\count($appliedCoupons) === 0) {
            return [];
        }

        $appliedCouponIds = [];
        /** @var Coupon $appliedCoupon */
        foreach ($appliedCoupons as $appliedCoupon) {
            $appliedCouponIds[$appliedCoupon->getId()] = true;
        }

        return $appliedCouponIds;
    }

    private function filterRuleOwnersWithCoupons(array $ruleOwnersWithCoupons, array $appliedCouponIds): array
    {
        $filteredRuleOwners = [];
        $promotions = [];
        $promotionIds = [];
        foreach ($ruleOwnersWithCoupons as $ruleOwner) {
            if ($ruleOwner instanceof Promotion) {
                $promotions[] = $ruleOwner;
                $promotionIds[] = $ruleOwner->getId();
            } else {
                $isCouponApplied = false;
                foreach ($ruleOwner->getCoupons() as $coupon) {
                    if (isset($appliedCouponIds[$coupon->getId()])) {
                        unset($appliedCouponIds[$coupon->getId()]);
                        $isCouponApplied = true;
                        break;
                    }
                }
                if ($isCouponApplied) {
                    $filteredRuleOwners[] = $ruleOwner;
                }
            }
        }

        if ($promotions) {
            // ensure that numeric coupon ids are passed as strings
            $couponIds = array_keys($appliedCouponIds);
            $matchedPromotionIds = $this->getMatchedPromotionsIds($promotionIds, $couponIds);
            foreach ($promotions as $promotion) {
                if (isset($matchedPromotionIds[$promotion->getId()])) {
                    $filteredRuleOwners[] = $promotion;
                }
            }
        }

        return $filteredRuleOwners;
    }

    private function getMatchedPromotionsIds(array $promotionIds, array $couponIds): array
    {
        sort($couponIds, SORT_NUMERIC);
        sort($promotionIds, SORT_NUMERIC);
        $cacheKey = implode(',', $couponIds) . '|' . implode(',', $promotionIds);
        if (!isset($this->matchedPromotionIds[$cacheKey])) {
            $this->matchedPromotionIds[$cacheKey] = array_fill_keys(
                $this->getCouponRepository()->getPromotionsWithMatchedCouponsIds($promotionIds, $couponIds),
                true
            );
        }

        return $this->matchedPromotionIds[$cacheKey];
    }

    private function getCouponRepository(): CouponRepository
    {
        return $this->doctrine->getRepository(Coupon::class);
    }
}

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
        $filteredRuleOwners = $this->doFilterRuleOwners($ruleOwners, $this->getAppliedCouponCodes($context));

        return $this->baseFiltrationService->getFilteredRuleOwners($filteredRuleOwners, $context);
    }

    private function doFilterRuleOwners(array $ruleOwners, array $appliedCouponCodes): array
    {
        $filteredRuleOwners = [];
        $ruleOwnersWithCoupons = [];
        foreach ($ruleOwners as $ruleOwner) {
            if (!$ruleOwner instanceof PromotionDataInterface) {
                continue;
            }
            if (!$ruleOwner->isUseCoupons()) {
                $filteredRuleOwners[] = $ruleOwner;
            } elseif ($appliedCouponCodes) {
                $ruleOwnersWithCoupons[] = $ruleOwner;
            }
        }

        if ($ruleOwnersWithCoupons) {
            $filteredRuleOwners = array_merge(
                $filteredRuleOwners,
                $this->filterRuleOwnersWithCoupons($ruleOwnersWithCoupons, $appliedCouponCodes)
            );
        }

        return $filteredRuleOwners;
    }

    private function getAppliedCouponCodes(array $context): array
    {
        $appliedCoupons = $context[ContextDataConverterInterface::APPLIED_COUPONS] ?? [];
        if (\count($appliedCoupons) === 0) {
            return [];
        }

        $appliedCouponCodes = [];
        /** @var Coupon $appliedCoupon */
        foreach ($appliedCoupons as $appliedCoupon) {
            $appliedCouponCodes[$appliedCoupon->getCode()] = true;
        }

        return $appliedCouponCodes;
    }

    private function filterRuleOwnersWithCoupons(array $ruleOwnersWithCoupons, array $appliedCouponCodes): array
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
                    if (isset($appliedCouponCodes[$coupon->getCode()])) {
                        unset($appliedCouponCodes[$coupon->getCode()]);
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
            // ensure that numeric coupon codes are passed as strings
            $couponCodes = array_map('strval', array_keys($appliedCouponCodes));
            $matchedPromotionIds = $this->getMatchedPromotionsIds($promotionIds, $couponCodes);
            foreach ($promotions as $promotion) {
                if (isset($matchedPromotionIds[$promotion->getId()])) {
                    $filteredRuleOwners[] = $promotion;
                }
            }
        }

        return $filteredRuleOwners;
    }

    private function getMatchedPromotionsIds(array $promotionIds, array $couponCodes): array
    {
        sort($couponCodes, SORT_STRING);
        sort($promotionIds, SORT_NUMERIC);
        $cacheKey = implode(',', $couponCodes) . '|' . implode(',', $promotionIds);
        if (!isset($this->matchedPromotionIds[$cacheKey])) {
            $this->matchedPromotionIds[$cacheKey] = array_fill_keys(
                $this->getCouponRepository()->getPromotionsWithMatchedCoupons($promotionIds, $couponCodes),
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

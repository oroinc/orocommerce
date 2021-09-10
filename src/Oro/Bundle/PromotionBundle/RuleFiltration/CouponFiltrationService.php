<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class CouponFiltrationService extends AbstractSkippableFiltrationService
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        ManagerRegistry $registry
    ) {
        $this->filtrationService = $filtrationService;
        $this->registry  = $registry;
    }

    /**
     * {@inheritdoc}
     */
    protected function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $ruleOwners = array_filter($ruleOwners, function ($ruleOwner) {
            return $ruleOwner instanceof PromotionDataInterface;
        });

        $ruleOwnersNoCoupons = $this->extractRuleOwnersNotUsingCoupons($ruleOwners);

        $ruleOwners = array_merge($ruleOwnersNoCoupons, $this->filterRuleOwnersWithCoupons($ruleOwners, $context));

        return $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context);
    }

    /**
     * @param array|PromotionDataInterface[] $ruleOwners
     * @return array
     */
    private function extractRuleOwnersNotUsingCoupons(array &$ruleOwners): array
    {
        $ruleOwnersNotUsingCoupons = [];
        foreach ($ruleOwners as $key => $ruleOwner) {
            if (!$ruleOwner->isUseCoupons()) {
                $ruleOwnersNotUsingCoupons[] = $ruleOwner;
                unset($ruleOwners[$key]);
            }
        }

        return $ruleOwnersNotUsingCoupons;
    }

    /**
     * @param array|PromotionDataInterface[] $ruleOwners
     * @param array $context
     * @return array
     */
    private function filterRuleOwnersWithCoupons(array $ruleOwners, array $context): array
    {
        if (!array_key_exists(ContextDataConverterInterface::APPLIED_COUPONS, $context)) {
            return [];
        }

        $promotions = [];
        foreach ($ruleOwners as $key => $ruleOwner) {
            if ($ruleOwner instanceof Promotion) {
                $promotions[] = $ruleOwner;
                unset($ruleOwners[$key]);
            }
        }

        $couponCodes = [];
        /** @var Coupon $appliedCoupon */
        foreach ($context[ContextDataConverterInterface::APPLIED_COUPONS] as $appliedCoupon) {
            $couponCodes[$appliedCoupon->getCode()] = true;
        }

        $promotionsData = $this->filterPromotionsDataWithCoupons($ruleOwners, $couponCodes);

        return array_merge(
            $promotionsData,
            $this->filterPromotionsWithCoupons($promotions, $couponCodes)
        );
    }

    /**
     * @param array|Promotion[] $promotions
     * @param array|string[] $couponCodes
     * @return array
     */
    private function filterPromotionsWithCoupons(array $promotions, array $couponCodes): array
    {
        if (empty($promotions)) {
            return [];
        }

        $matchedPromotionsIds = $this->registry
            ->getManagerForClass(Coupon::class)
            ->getRepository(Coupon::class)
            ->getPromotionsWithMatchedCoupons($promotions, array_keys($couponCodes));

        $matchedPromotionsIds = array_flip($matchedPromotionsIds);

        return array_filter($promotions, function ($promotion) use ($matchedPromotionsIds) {
            /** @var Promotion $promotion */
            return isset($matchedPromotionsIds[$promotion->getId()]);
        });
    }

    /**
     * @param array|PromotionDataInterface[] $promotionsData
     * @param array $couponCodes
     * @return array
     */
    private function filterPromotionsDataWithCoupons(array $promotionsData, array &$couponCodes): array
    {
        return array_filter($promotionsData, function ($promotionData) use (&$couponCodes) {
            /** @var PromotionDataInterface $promotionData */
            foreach ($promotionData->getCoupons() as $coupon) {
                if (isset($couponCodes[$coupon->getCode()])) {
                    unset($couponCodes[$coupon->getCode()]);
                    return true;
                }
            }

            return false;
        });
    }
}

<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
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
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param RuleFiltrationServiceInterface $filtrationService
     * @param ManagerRegistry $registry
     */
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
    private function getFilteredPromotions(array $ruleOwners, array $context): array
    {
        $appliedCoupons = new ArrayCollection();
        $matchedPromotionsIds = [];
        if (array_key_exists(ContextDataConverterInterface::APPLIED_COUPONS, $context)) {
            $appliedCoupons = $context[ContextDataConverterInterface::APPLIED_COUPONS];
            $matchedPromotionsIds = $this->registry
                ->getManagerForClass(Coupon::class)
                ->getRepository(Coupon::class)
                ->getPromotionsWithMatchedCoupons(
                    $this->getPromotionsIds($ruleOwners),
                    $this->getCouponCodes($appliedCoupons)
                );
        }

        $ruleOwners = $this->filterNotApplicableRuleOwners($ruleOwners, $appliedCoupons);

        return array_values(array_filter($ruleOwners, function ($ruleOwner) use ($matchedPromotionsIds) {
            /** @var PromotionDataInterface $ruleOwner */
            if (!$ruleOwner->isUseCoupons()) {
                return true;
            }

            return in_array($ruleOwner->getId(), $matchedPromotionsIds);
        }));
    }

    /**
     * @param array $ruleOwners
     * @return array
     */
    private function getPromotionsIds(array $ruleOwners): array
    {
        return array_map(function ($ruleOwner) {
            /** @var PromotionDataInterface $ruleOwner */
            return $ruleOwner->getId();
        }, $ruleOwners);
    }

    /**
     * @param Collection $appliedCoupons
     * @return array
     */
    private function getCouponCodes($appliedCoupons): array
    {
        return $appliedCoupons->map(
            function (Coupon $coupon) {
                return $coupon->getCode();
            }
        )->toArray();
    }

    /**
     * @param array $ruleOwners
     * @param Collection $appliedCoupons
     * @return array
     */
    private function filterNotApplicableRuleOwners($ruleOwners, $appliedCoupons): array
    {
        return array_filter($ruleOwners, function ($ruleOwner) use ($appliedCoupons) {
            if (!$ruleOwner instanceof PromotionDataInterface) {
                return false;
            }

            if ($ruleOwner->isUseCoupons() && $appliedCoupons->isEmpty()) {
                return false;
            }

            return true;
        });
    }
}

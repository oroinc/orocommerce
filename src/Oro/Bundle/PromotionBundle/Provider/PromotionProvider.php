<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotionsAwareInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\RuleFiltration\AbstractSkippableFiltrationService;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides information about promotions applicable to a specific source entity.
 */
class PromotionProvider
{
    private ManagerRegistry $doctrine;
    private RuleFiltrationServiceInterface $ruleFiltrationService;
    private ContextDataConverterInterface $contextDataConverter;
    private AppliedPromotionMapper $promotionMapper;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        ManagerRegistry $doctrine,
        RuleFiltrationServiceInterface $ruleFiltrationService,
        ContextDataConverterInterface $contextDataConverter,
        AppliedPromotionMapper $promotionMapper,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->doctrine = $doctrine;
        $this->ruleFiltrationService = $ruleFiltrationService;
        $this->contextDataConverter = $contextDataConverter;
        $this->promotionMapper = $promotionMapper;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param object $sourceEntity
     *
     * @return PromotionDataInterface[]
     */
    public function getPromotions(object $sourceEntity): array
    {
        $promotions = [];
        if ($sourceEntity instanceof AppliedPromotionsAwareInterface) {
            $promotions = $this->getAppliedPromotions($sourceEntity);
        }
        $promotions = array_merge($promotions, $this->getAllPromotions());

        return $this->filterPromotions($sourceEntity, $promotions);
    }

    /**
     * Checks whether promotion has been already applied to a given source entity.
     *
     * @param object                 $sourceEntity
     * @param PromotionDataInterface $promotion
     *
     * @return bool
     */
    public function isPromotionApplied(object $sourceEntity, PromotionDataInterface $promotion): bool
    {
        $promotions = $this->getPromotions($sourceEntity);
        foreach ($promotions as $appliedPromotion) {
            if ($appliedPromotion->getId() === $promotion->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks whether promotion can be applied to a given source entity.
     *
     * @param object                 $sourceEntity
     * @param PromotionDataInterface $promotion
     * @param string[]               $skipFilters
     *
     * @return bool
     */
    public function isPromotionApplicable(
        object $sourceEntity,
        PromotionDataInterface $promotion,
        array $skipFilters = []
    ): bool {
        return !empty($this->filterPromotions($sourceEntity, [$promotion], $skipFilters));
    }

    /**
     * @param object                   $sourceEntity
     * @param PromotionDataInterface[] $promotions
     * @param string[]                 $skipFilters
     *
     * @return RuleOwnerInterface[]
     */
    private function filterPromotions(object $sourceEntity, array $promotions, array $skipFilters = []): array
    {
        $contextData = $this->contextDataConverter->getContextData($sourceEntity);
        if (!empty($skipFilters)) {
            $contextData[AbstractSkippableFiltrationService::SKIP_FILTERS_KEY] = $skipFilters;
        }

        return $this->ruleFiltrationService->getFilteredRuleOwners($promotions, $contextData);
    }

    /**
     * @return PromotionDataInterface[]
     */
    private function getAllPromotions(): array
    {
        $organizationId = $this->tokenAccessor->getOrganizationId();
        if (null === $organizationId) {
            return [];
        }

        return $this->doctrine->getRepository(Promotion::class)->getAllPromotions($organizationId);
    }

    /**
     * @param AppliedPromotionsAwareInterface $sourceEntity
     *
     * @return PromotionDataInterface[]
     */
    private function getAppliedPromotions(AppliedPromotionsAwareInterface $sourceEntity): array
    {
        $appliedPromotions = [];
        foreach ($sourceEntity->getAppliedPromotions() as $appliedPromotionEntity) {
            if (!$appliedPromotionEntity->getPromotionData()) {
                continue;
            }
            $appliedPromotions[] = $this->promotionMapper->mapAppliedPromotionToPromotionData($appliedPromotionEntity);
        }

        return $appliedPromotions;
    }
}

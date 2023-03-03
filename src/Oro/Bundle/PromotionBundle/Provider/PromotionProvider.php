<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Entity\Repository\PromotionRepository;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\RuleFiltration\AbstractSkippableFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides information about promotions applicable to a specific source entity.
 */
class PromotionProvider
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private RuleFiltrationServiceInterface $ruleFiltrationService,
        private ContextDataConverterInterface $contextDataConverter,
        private AppliedPromotionMapper $promotionMapper,
        private TokenAccessorInterface $tokenAccessor,
        private MemoryCacheProviderInterface $memoryCacheProvider,
        private PromotionAwareEntityHelper $promotionAwareHelper
    ) {
    }

    public function getPromotions(object $sourceEntity): array
    {
        $promotions = [];

        if ($this->promotionAwareHelper->isPromotionAware($sourceEntity)) {
            $promotions = $this->getAppliedPromotions($sourceEntity);
        }
        $contextData = $this->contextDataConverter->getContextData($sourceEntity);
        $availablePromotions = $this->getAvailablePromotions($sourceEntity, $contextData);
        $promotions = array_merge($promotions, $availablePromotions);

        return $this->filterPromotions($contextData, $promotions);
    }

    /**
     * Checks whether promotion has been already applied to a given source entity.
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
     */
    public function isPromotionApplicable(
        object $sourceEntity,
        PromotionDataInterface $promotion,
        array $skipFilters = []
    ): bool {
        $contextData = $this->contextDataConverter->getContextData($sourceEntity);

        return !empty($this->filterPromotions($contextData, [$promotion], $skipFilters));
    }

    private function getAvailablePromotions(object $sourceEntity, array $contextData): array
    {
        $organization = $this->tokenAccessor->getOrganizationId();
        if (null === $organization) {
            return [];
        }

        return $this->memoryCacheProvider->get(
            ['entity_hash' => spl_object_hash($sourceEntity)],
            function () use ($sourceEntity, $contextData, $organization) {
                $criteria = $contextData[ContextDataConverterInterface::CRITERIA] ?? null;
                $currentCurrency = $contextData[ContextDataConverterInterface::CURRENCY] ?? null;

                /** @var PromotionRepository $promotionRepository */
                $promotionRepository = $this->doctrine->getRepository(Promotion::class);

                return $promotionRepository->getAvailablePromotions($criteria, $currentCurrency, $organization);
            }
        );
    }

    private function filterPromotions(array $contextData, array $promotions, array $skipFilters = []): array
    {
        if (!empty($skipFilters)) {
            $contextData[AbstractSkippableFiltrationService::SKIP_FILTERS_KEY] = $skipFilters;
        }

        return $this->ruleFiltrationService->getFilteredRuleOwners($promotions, $contextData);
    }

    private function getAppliedPromotions(object $sourceEntity): array
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

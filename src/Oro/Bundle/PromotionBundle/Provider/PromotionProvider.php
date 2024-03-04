<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
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
    private AvailablePromotionProviderInterface $availablePromotionProvider;

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

    public function setAvailablePromotionProvider(AvailablePromotionProviderInterface $availablePromotionProvider)
    {
        $this->availablePromotionProvider = $availablePromotionProvider;
    }

    /**
     * @param object $sourceEntity
     *
     * @return PromotionDataInterface[]
     */
    public function getPromotions(object $sourceEntity): array
    {
        $promotions = [];
        if ($this->promotionAwareHelper->isPromotionAware($sourceEntity)) {
            $promotions = $this->getAppliedPromotions($sourceEntity);
        }

        $contextData = $this->contextDataConverter->getContextData($sourceEntity);
        $promotions = array_merge($promotions, $this->getAvailablePromotions($sourceEntity, $contextData));

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
        return $this->memoryCacheProvider->get(
            ['entity_hash'  => spl_object_hash($sourceEntity)],
            function () use ($contextData) {
                return $this->availablePromotionProvider->getAvailablePromotions($contextData);
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
        $result = [];
        $appliedPromotions = $sourceEntity->getAppliedPromotions();
        foreach ($appliedPromotions as $appliedPromotion) {
            if ($appliedPromotion->getPromotionData()) {
                $result[] = $this->promotionMapper->mapAppliedPromotionToPromotionData($appliedPromotion);
            }
        }

        return $result;
    }
}

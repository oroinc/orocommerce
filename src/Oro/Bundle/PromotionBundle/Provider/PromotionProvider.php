<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class PromotionProvider
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var RuleFiltrationServiceInterface
     */
    private $ruleFiltrationService;

    /**
     * @var ContextDataConverterInterface
     */
    private $contextDataConverter;

    /**
     * @param ManagerRegistry $registry
     * @param RuleFiltrationServiceInterface $ruleFiltrationService
     * @param ContextDataConverterInterface $contextDataConverter
     */
    public function __construct(
        ManagerRegistry $registry,
        RuleFiltrationServiceInterface $ruleFiltrationService,
        ContextDataConverterInterface $contextDataConverter
    ) {
        $this->registry = $registry;
        $this->ruleFiltrationService = $ruleFiltrationService;
        $this->contextDataConverter = $contextDataConverter;
    }

    /**
     * @param object $sourceEntity
     * @return array|Promotion[]
     */
    public function getPromotions($sourceEntity): array
    {
        $promotions = $this->registry
            ->getManagerForClass(Promotion::class)
            ->getRepository(Promotion::class)
            ->findAll();

        return $this->filterPromotions($sourceEntity, $promotions);
    }

    /**
     * Checks whether promotion has been already applied to a given source entity.
     *
     * @param object $sourceEntity
     * @param Promotion $promotion
     * @return bool
     */
    public function isPromotionApplied($sourceEntity, Promotion $promotion): bool
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
     * @param $sourceEntity
     * @param Promotion $promotion
     * @return bool
     */
    public function isPromotionApplicable($sourceEntity, Promotion $promotion): bool
    {
        return !empty($this->filterPromotions($sourceEntity, [$promotion]));
    }

    /**
     * @param object $sourceEntity
     * @param array|Promotion[] $promotions
     * @return array|\Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface[]
     */
    private function filterPromotions($sourceEntity, array $promotions): array
    {
        $contextData = $this->contextDataConverter->getContextData($sourceEntity);

        return $this->ruleFiltrationService->getFilteredRuleOwners($promotions, $contextData);
    }
}

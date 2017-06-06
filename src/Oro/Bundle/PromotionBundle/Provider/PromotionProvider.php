<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Context\ContextDataConverterInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
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
     * @var RuleFiltrationServiceInterface
     */
    private $matchingItemsFiltrationService;

    /**
     * @var ContextDataConverterInterface
     */
    private $contextDataConverter;

    /**
     * @var DiscountLineItemMatcher
     */
    private $discountLineItemMatcher;

    /**
     * @param ManagerRegistry $registry
     * @param RuleFiltrationServiceInterface $ruleFiltrationService
     * @param ContextDataConverterInterface $contextDataConverter
     */
    public function __construct(
        ManagerRegistry $registry,
        RuleFiltrationServiceInterface $ruleFiltrationService,
        RuleFiltrationServiceInterface $matchingItemsFiltrationService,
        ContextDataConverterInterface $contextDataConverter,
        DiscountLineItemMatcher $discountLineItemMatcher
    ) {
        $this->registry = $registry;
        $this->ruleFiltrationService = $ruleFiltrationService;
        $this->contextDataConverter = $contextDataConverter;
        $this->discountLineItemMatcher = $discountLineItemMatcher;
        $this->matchingItemsFiltrationService = $matchingItemsFiltrationService;
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

        $contextData = $this->contextDataConverter->getContextData($sourceEntity);

        $promotions = $this->ruleFiltrationService->getFilteredRuleOwners($promotions, $contextData);
        $this->discountLineItemMatcher->markApplicableItems($contextData['lineItem'], $promotions);

        return $this->matchingItemsFiltrationService->getFilteredRuleOwners($promotions, $contextData);
    }
}

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
     * @return array|RuleOwnerInterface[]
     */
    public function getPromotions($sourceEntity): array
    {
        $promotions = $this->registry
            ->getManagerForClass(Promotion::class)
            ->getRepository(Promotion::class)
            ->findAll();

        $contextData = $this->contextDataConverter->getContextData($sourceEntity);

        return $this->ruleFiltrationService->getFilteredRuleOwners($promotions, $contextData);
    }
}

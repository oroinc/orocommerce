<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PromotionBundle\Context\ContextConverterInterface;
use Oro\Bundle\PromotionBundle\Context\PromotionContextInterface;
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
     * @var ContextConverterInterface
     */
    private $contextConverter;

    /**
     * @param ManagerRegistry $registry
     * @param RuleFiltrationServiceInterface $ruleFiltrationService
     * @param ContextConverterInterface $contextConverter
     */
    public function __construct(
        ManagerRegistry $registry,
        RuleFiltrationServiceInterface $ruleFiltrationService,
        ContextConverterInterface $contextConverter
    ) {
        $this->registry = $registry;
        $this->ruleFiltrationService = $ruleFiltrationService;
        $this->contextConverter = $contextConverter;
    }

    /**
     * @param PromotionContextInterface $context
     * @return array|RuleOwnerInterface[]
     */
    public function getPromotions(PromotionContextInterface $context): array
    {
        $promotions = $this->registry
            ->getManagerForClass(Promotion::class)
            ->getRepository(Promotion::class)
            ->findAll();

        $contextData = $this->contextConverter->convert($context);
        return $this->ruleFiltrationService->getFilteredRuleOwners($promotions, $contextData);
    }
}

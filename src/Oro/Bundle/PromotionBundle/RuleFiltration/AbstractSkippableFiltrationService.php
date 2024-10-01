<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * The base class for skippable filtration services.
 */
abstract class AbstractSkippableFiltrationService implements RuleFiltrationServiceInterface
{
    public const SKIP_FILTERS_KEY = 'skip_filters';

    #[\Override]
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        if (!empty($context[self::SKIP_FILTERS_KEY][\get_class($this)])) {
            return $ruleOwners;
        }

        return $this->filterRuleOwners($ruleOwners, $context);
    }

    /**
     * @param RuleOwnerInterface[] $ruleOwners
     * @param array                $context
     *
     * @return RuleOwnerInterface[]
     */
    abstract protected function filterRuleOwners(array $ruleOwners, array $context): array;
}

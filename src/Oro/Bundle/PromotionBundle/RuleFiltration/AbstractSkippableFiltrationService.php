<?php

namespace Oro\Bundle\PromotionBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * This class makes filtration service skippable by passing an option with skipped filtration service
 * class into context.
 */
abstract class AbstractSkippableFiltrationService implements RuleFiltrationServiceInterface
{
    const SKIP_FILTERS_KEY = 'skip_filters';

    /**
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        if (!empty($context[self::SKIP_FILTERS_KEY][get_class($this)])) {
            return $ruleOwners;
        }

        return $this->filterRuleOwners($ruleOwners, $context);
    }

    /**
     * @param RuleOwnerInterface[]|array $ruleOwners
     * @param array $context
     * @return RuleOwnerInterface[]|array
     */
    abstract protected function filterRuleOwners(array $ruleOwners, array $context);
}

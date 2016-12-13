<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\PaymentBundle\Entity\RuleOwnerInterface;
use Oro\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionLanguageRuleFiltrationService
{
    /** @var ExpressionLanguage */
    private $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * @param RuleOwnerInterface[]|array $ruleOwners
     * @param array                      $values
     *
     * @return RuleOwnerInterface[]|array
     */
    public function getFilteredRuleOwners($ruleOwners, $values)
    {
        $applicableOwners = [];
        foreach ($ruleOwners as $ruleOwner) {
            $rule = $ruleOwner->getRule();

            if ($this->expressionApplicable($rule->getExpression(), $values)) {
                $applicableOwners[] = $ruleOwner;
                if ($rule->isStopProcessing()) {
                    break;
                }
            }
        }

        return $applicableOwners;
    }

    /**
     * @param string $expression
     * @param array  $values
     *
     * @return bool
     */
    private function expressionApplicable($expression, $values)
    {
        try {
            return (bool) $this->expressionLanguage->evaluate($expression, $values);
        } catch (\Exception $e) {
            return false;
        }
    }
}

<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Psr\Log\LoggerInterface;

/**
 * Filters out rule owners in case if an error was occurred during a rule evaluation.
 */
class ExpressionLanguageRuleFiltrationService implements RuleFiltrationServiceInterface
{
    public function __construct(
        private RuleFiltrationServiceInterface $baseFiltrationService,
        private ExpressionLanguage $expressionLanguage,
        private LoggerInterface $logger
    ) {
    }

    #[\Override]
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredRuleOwners = $this->filterRuleOwners($ruleOwners, $context);

        return $this->baseFiltrationService->getFilteredRuleOwners($filteredRuleOwners, $context);
    }

    private function filterRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredRuleOwners = [];
        foreach ($ruleOwners as $ruleOwner) {
            $ruleExpression = $ruleOwner->getRule()->getExpression();
            if (!$ruleExpression || $this->expressionApplicable($ruleExpression, $context, $ruleOwner)) {
                $filteredRuleOwners[] = $ruleOwner;
            }
        }

        return $filteredRuleOwners;
    }

    private function expressionApplicable(string $expression, array $values, RuleOwnerInterface $ruleOwner): bool
    {
        $result = false;

        set_error_handler(static function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
        }, E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

        try {
            $result = (bool) $this->expressionLanguage->evaluate($expression, $values);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Rule condition evaluation error: {error}. ' .
                '{rule_owner_class_name} with name "{rule_name}" was skipped.',
                [
                    'expression' => $expression,
                    'values' => $values,
                    'rule_owner' => $ruleOwner,
                    'error' => $e->getMessage(),
                    'rule_owner_class_name' => \get_class($ruleOwner),
                    'rule_name' => $ruleOwner->getRule()->getName()
                ]
            );
        }

        restore_error_handler();

        return $result;
    }
}

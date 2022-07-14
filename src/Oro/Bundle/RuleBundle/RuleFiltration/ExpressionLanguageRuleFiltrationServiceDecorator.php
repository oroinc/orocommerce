<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Psr\Log\LoggerInterface;

/**
 * This class makes rule not applicable in case if error was occurred during rule evaluation.
 */
class ExpressionLanguageRuleFiltrationServiceDecorator implements RuleFiltrationServiceInterface
{
    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /** @var LoggerInterface */
    private $logger;

    /** @var RuleFiltrationServiceInterface */
    private $filtrationService;

    public function __construct(
        ExpressionLanguage $expressionLanguage,
        RuleFiltrationServiceInterface $filtrationService,
        LoggerInterface $logger
    ) {
        $this->expressionLanguage = $expressionLanguage;
        $this->filtrationService = $filtrationService;
        $this->logger = $logger;
    }

    /**
     * @param RuleOwnerInterface[]|array $ruleOwners
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context)
    {
        $filteredOwners = [];
        foreach ($ruleOwners as $ruleOwner) {
            /** @var Rule $rule */
            $rule = $ruleOwner->getRule();

            if (!$rule->getExpression() || $this->expressionApplicable($rule->getExpression(), $context, $ruleOwner)) {
                $filteredOwners[] = $ruleOwner;
            }
        }

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param string $expression
     * @param array $values
     * @param RuleOwnerInterface $ruleOwner
     * @return bool
     * @throws \ErrorException
     */
    private function expressionApplicable($expression, $values, RuleOwnerInterface $ruleOwner): bool
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
                    'rule_owner_class_name' => get_class($ruleOwner),
                    'rule_name' => $ruleOwner->getRule()->getName()
                ]
            );
        }

        restore_error_handler();

        return $result;
    }
}

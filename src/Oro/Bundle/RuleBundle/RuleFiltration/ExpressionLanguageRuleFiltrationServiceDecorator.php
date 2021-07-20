<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

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
            $rule = $ruleOwner->getRule();

            if (!$rule->getExpression() || $this->expressionApplicable($rule->getExpression(), $context)) {
                $filteredOwners[] = $ruleOwner;
            }
        }

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    /**
     * @param string $expression
     * @param array  $values
     *
     * @return bool
     */
    private function expressionApplicable($expression, $values): bool
    {
        $result = false;

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, $severity, $severity, $file, $line);
        }, E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

        try {
            $result = (bool) $this->expressionLanguage->evaluate($expression, $values);
        } catch (\Exception $e) {
            $this->logger->error(
                'Rule condition evaluation error: ' . $e->getMessage(),
                ['expression' => $expression, 'values' => $values]
            );
        }

        restore_error_handler();

        return $result;
    }
}

<?php

namespace Oro\Bundle\RuleBundle\RuleFiltration;

use Oro\Bundle\RuleBundle\Entity\RuleOwnerInterface;
use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Psr\Log\LoggerInterface;

class ExpressionLanguageRuleFiltrationServiceDecorator implements RuleFiltrationServiceInterface
{
    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /** @var LoggerInterface */
    private $logger;

    /** @var RuleFiltrationServiceInterface */
    private $filtrationService;

    /**
     * @param RuleFiltrationServiceInterface $filtrationService
     * @param LoggerInterface $logger
     */
    public function __construct(RuleFiltrationServiceInterface $filtrationService, LoggerInterface $logger)
    {
        $this->expressionLanguage = new ExpressionLanguage();
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
    private function expressionApplicable($expression, $values)
    {
        try {
            return (bool) $this->expressionLanguage->evaluate($expression, $values);
        } catch (\Exception $e) {
            $this->logger->error(
                'Rule condition evaluation error: ' . $e->getMessage(),
                ['expression' => $expression, 'values' => $values]
            );
            return false;
        }
    }
}

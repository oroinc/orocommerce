<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\ORMException;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate price rule expression.
 * Check that expression may be converted to a valid SQL.
 */
class PriceRuleExpressionsValidator extends ConstraintValidator
{
    /**
     * @var PriceListRuleCompiler
     */
    private $compiler;

    /**
     * @var bool
     */
    private $isDebug;

    public function __construct(
        PriceListRuleCompiler $compiler,
        bool $isDebug = false
    ) {
        $this->compiler = $compiler;
        $this->isDebug = $isDebug;
    }

    /**
     * {@inheritdoc}
     * @param PriceRule $value
     * @param PriceRuleExpressions $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof PriceRule) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    PriceRule::class,
                    is_object($value) ? ClassUtils::getClass($value) : gettype($value)
                )
            );
        }

        if ($value->getRule()) {
            $this->validateCalculateAs($value, $constraint);
        }
        if ($value->getRuleCondition()) {
            $this->validateCondition($value, $constraint);
        }
    }

    private function validateCalculateAs(PriceRule $value, PriceRuleExpressions $constraint)
    {
        $rule = clone $value;
        $rule->setRuleCondition(null);
        $this->validateRulePart($rule, $constraint, 'rule');
    }

    private function validateCondition(PriceRule $value, PriceRuleExpressions $constraint)
    {
        $rule = clone $value;
        $rule->setRule(1);
        $this->validateRulePart($rule, $constraint, 'ruleCondition');
    }

    private function validateRulePart(PriceRule $rule, PriceRuleExpressions $constraint, string $field)
    {
        try {
            $qb = $this->compiler->compileQueryBuilder($rule);
            $qb->setMaxResults(0);
            $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            if (!$e instanceof DBALException && !$e instanceof ORMException) {
                return;
            }
            $this->context
                ->buildViolation($this->isDebug ? $e->getMessage() : $constraint->message)
                ->atPath($field)
                ->addViolation();
        }
    }
}

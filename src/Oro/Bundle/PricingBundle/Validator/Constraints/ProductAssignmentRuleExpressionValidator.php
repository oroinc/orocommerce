<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\ORMException;
use Oro\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate price list product assignment rule expression.
 * Check that expression may be converted to a valid SQL.
 */
class ProductAssignmentRuleExpressionValidator extends ConstraintValidator
{
    /**
     * @var ProductAssignmentRuleCompiler
     */
    private $compiler;

    /**
     * @var bool
     */
    private $isDebug;

    public function __construct(
        ProductAssignmentRuleCompiler $compiler,
        bool $isDebug = false
    ) {
        $this->compiler = $compiler;
        $this->isDebug = $isDebug;
    }

    /**
     * {@inheritdoc}
     * @param PriceRule $value
     * @param ProductAssignmentRuleExpression $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof PriceList) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    PriceList::class,
                    is_object($value) ? ClassUtils::getClass($value) : gettype($value)
                )
            );
        }

        if (!$value->getProductAssignmentRule()) {
            return;
        }

        try {
            $qb = $this->compiler->compileQueryBuilder($value);
            $qb->setMaxResults(0);
            $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            if (!$e instanceof DBALException && !$e instanceof ORMException) {
                return;
            }
            $this->context
                ->buildViolation($this->isDebug ? $e->getMessage() : $constraint->message)
                ->atPath('productAssignmentRule')
                ->addViolation();
        }
    }
}

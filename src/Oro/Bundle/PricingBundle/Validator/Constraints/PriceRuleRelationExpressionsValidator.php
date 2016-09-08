<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\RelationNode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PriceRuleRelationExpressionsValidator extends ConstraintValidator
{
    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @param ExpressionParser $parser
     */
    public function __construct(ExpressionParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * {@inheritdoc}
     * @param PriceRule $value
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->validateCurrency($value);
    }

    /**
     * @param PriceRule $rule
     */
    protected function validateCurrency(PriceRule $rule)
    {
        $expression = $rule->getCurrencyExpression();
        if (!$expression) {
            return;
        }

        /** @var NodeInterface[] $nodes */
        $nodes = $this->parser->parse($expression)->getNodes();
        $path = 'currencyExpression';
        if (count($nodes) !== 1) {
            $this->addError($path, 'to many nodes');
            return;
        }

        /** @var RelationNode $node */
        $node = $nodes[0];
        if (!$node instanceof RelationNode) {
            $this->addError($path, 'invalid node');
            return;
        }

        if (!$node->getField() !== 'currency') {
            $this->addError($path, 'invalid node');
            return;
        }

        /** @var  $ruleNode */
        foreach ($this->parser->parse($expression)->getNodes() as $ruleNode) {
//            if ($ruleNode instanceof RelationNode && $ruleNode->)
        }
    }

    /**
     * @param PriceRule $rule
     */
    protected function validateProductUnit(PriceRule $rule)
    {
        $expression = $rule->getProductUnitExpression();
        if (!$expression) {
            return;
        }

        /** @var NodeInterface[] $nodes */
        $nodes = $this->parser->parse($expression)->getNodes();
        $path = 'currencyExpression';
        if (count($nodes) !== 1) {
            $this->addError($path, 'to many nodes');
            return;
        }

        /** @var RelationNode $node */
        $node = $nodes[0];
        if (!$node instanceof RelationNode) {
            $this->addError($path, 'invalid node');
            return;
        }

        if (!$node->getField() !== 'currency') {
            $this->addError($path, 'invalid node');
            return;
        }

        /** @var  $ruleNode */
        foreach ($this->parser->parse($expression)->getNodes() as $ruleNode) {
//            if ($ruleNode instanceof RelationNode && $ruleNode->)
        }
    }

    /**
     * @param string $path
     * @param string $message
     */
    protected function addError($path, $message)
    {
        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $context->buildViolation($message)
            ->atPath($path)
            ->addViolation();
    }
}

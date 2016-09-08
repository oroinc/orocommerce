<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\RelationNode;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SearchBundle\Exception\ExpressionSyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PriceRuleRelationExpressionsValidator extends ConstraintValidator
{
    const FIELD_ARE_NOT_ALLOWED_MESSAGE = 'oro.pricing.validators.field_are_not_allowed.message';
    const ONE_EXPRESSION_ALLOWED_MESSAGE = 'oro.pricing.validators.one_expression_allowed.message';
    const RELATION_NOT_IN_RULE_MESSAGE = 'oro.pricing.validators.relation_not_in_rule.message';
    const ONLY_PRICE_RELATION_MESSAGE = 'oro.pricing.validators.only_price_relations_available.message';

    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldsProvider;

    /**
     * @param ExpressionParser $parser
     * @param PriceRuleFieldsProvider $fieldsProvider
     */
    public function __construct(
        ExpressionParser $parser,
        PriceRuleFieldsProvider $fieldsProvider
    ) {
        $this->parser = $parser;
        $this->fieldsProvider = $fieldsProvider;
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
        $this->validateProductUnit($value);
    }

    /**
     * @param PriceRule $rule
     */
    protected function validateCurrency(PriceRule $rule)
    {
        $expression = $rule->getCurrencyExpression();
        $path = 'currencyExpression';
        if (
            !$expression ||
            null === ($nodes = $this->getNodes($expression, $path)) ||
            !$this->validateNodesCount($nodes, $path) ||
            !$this->validateIsRelationNode($nodes[0], $path) ||
            !$this->validateIsRelationInRule($rule, $nodes[0], $path)
        ) {
            return;
        }

        $node = $nodes[0];
        if ($node->getRelationField() !== 'currency') {
            $this->addError($path, self::FIELD_ARE_NOT_ALLOWED_MESSAGE, ['%fieldName%' => $node->getRelationField()]);
        }
    }

    /**
     * @param PriceRule $rule
     */
    protected function validateProductUnit(PriceRule $rule)
    {
        $expression = $rule->getProductUnitExpression();
        $path = 'productUnitExpression';
        if (
            !$expression ||
            null === ($nodes = $this->getNodes($expression, $path)) ||
            !$this->validateNodesCount($nodes, $path) ||
            !$this->validateIsRelationNode($nodes[0], $path) ||
            !$this->validateIsRelationInRule($rule, $nodes[0], $path)
        ) {
            return;
        }

        /** @var RelationNode $node */
        $node = $nodes[0];
        $relationContainerClassName = $this->fieldsProvider->getRealClassName($node->getContainer(), $node->getField());
        $relationClassName = $this->fieldsProvider
            ->getRealClassName(
                $relationContainerClassName,
                $node->getRelationField()
            );
        if (!is_subclass_of($relationClassName, ProductUnit::class)) {
            $this->addError($path, self::FIELD_ARE_NOT_ALLOWED_MESSAGE, ['%fieldName%' => $node->getRelationField()]);
        }
    }

    /**
     * @param array $nodes
     * @param string $path
     * @return bool
     */
    protected function validateNodesCount(array $nodes, $path)
    {
        if (count($nodes) !== 1) {
            $this->addError($path, self::ONE_EXPRESSION_ALLOWED_MESSAGE);
            return false;
        }
        return true;
    }

    /**
     * @param NodeInterface $node
     * @param string $path
     * @return bool
     */
    protected function validateIsRelationNode(NodeInterface $node, $path)
    {
        if (!$node instanceof RelationNode) {
            $this->addError($path, self::ONLY_PRICE_RELATION_MESSAGE);
            return false;
        }
        return true;
    }

    /**
     * @param PriceRule $rule
     * @param RelationNode $node
     * @param string $path
     * @return bool
     */
    protected function validateIsRelationInRule(PriceRule $rule, RelationNode $node, $path)
    {
        if (!$this->isRelationInRule($rule, $node)) {
            $this->addError($path, self::RELATION_NOT_IN_RULE_MESSAGE, ['%relationName%' => $node->getField()]);
            return false;
        }
        return true;
    }

    /**
     * @param string $expression
     * @param string $path
     * @return array|NodeInterface[]
     */
    protected function getNodes($expression, $path)
    {
        try {
            return $this->parser->parse($expression)->getNodes();
        } catch (ExpressionSyntaxError $e) {
            $this->addError($path, $e->getMessage());
            return null;
        }
    }

    /**
     * @param PriceRule $rule
     * @param RelationNode $node
     * @return bool
     */
    public function isRelationInRule(PriceRule $rule, RelationNode $node)
    {
        foreach ($this->parser->parse($rule->getRule())->getNodes() as $ruleNode) {
            if ($ruleNode instanceof RelationNode && $this->isSameRelation($ruleNode, $node)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param RelationNode $node
     * @param RelationNode $other
     * @return bool
     */
    public function isSameRelation(RelationNode $node, RelationNode $other)
    {
        return $node->getContainer() == $other->getContainer() &&
        $node->getContainerId() == $other->getContainerId() &&
        $node->getField() == $other->getField();
    }

    /**
     * @param string $path
     * @param string $message
     * @param array $params
     */
    protected function addError($path, $message, array $params = [])
    {
        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $context->buildViolation($message, $params)
            ->atPath($path)
            ->addViolation();
    }
}

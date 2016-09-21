<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Expression\NameNode;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\RelationNode;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleType;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PriceRuleRelationExpressionsValidator extends ConstraintValidator
{
    const FIELD_ARE_NOT_ALLOWED_MESSAGE = 'oro.pricing.validators.field_are_not_allowed.message';
    const ONE_EXPRESSION_ALLOWED_MESSAGE = 'oro.pricing.validators.one_expression_allowed.message';
    const RELATION_NOT_IN_RULE_MESSAGE = 'oro.pricing.validators.relation_not_in_rule.message';
    const ONLY_PRICE_RELATION_MESSAGE = 'oro.pricing.validators.only_price_relations_available.message';
    const TOO_MANY_RELATIONS_MESSAGE = 'oro.pricing.validators.too_many_relations.message';

    const QUANTITY_FIELD_NAME = 'oro.pricing.pricerule.quantity.label';
    const CURRENCY_FIELD_NAME = 'oro.pricing.pricerule.currency.label';
    const PRODUCT_UNIT_FIELD_NAME = 'oro.pricing.pricerule.product_unit.label';

    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldsProvider;

    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @param ExpressionParser $parser
     * @param PriceRuleFieldsProvider $fieldsProvider
     * @param Translator $translator
     */
    public function __construct(
        ExpressionParser $parser,
        PriceRuleFieldsProvider $fieldsProvider,
        Translator $translator
    ) {
        $this->parser = $parser;
        $this->fieldsProvider = $fieldsProvider;

        $this->translator = $translator;
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
        $this->validateQuantity($value);
    }

    /**
     * @param PriceRule $rule
     */
    protected function validateCurrency(PriceRule $rule)
    {
        $inputName = $this->translator->trans(self::CURRENCY_FIELD_NAME);
        $expression = $rule->getCurrencyExpression();
        $path = PriceRuleType::CURRENCY_EXPRESSION;
        $nodes = $this->getNodes($expression, $path) ?: [];
        if (!$this->checkNodes($rule, $nodes, $path, $inputName)) {
            return;
        }

        $node = $nodes[0];
        if ($node->getRelationField() !== 'currency') {
            $this->addError($path, self::FIELD_ARE_NOT_ALLOWED_MESSAGE, [
                '%fieldName%' => $node->getRelationField(),
                '%inputName%' => $inputName
            ]);
        }
    }

    /**
     * @param PriceRule $rule
     */
    protected function validateProductUnit(PriceRule $rule)
    {
        $inputName = $this->translator->trans(self::PRODUCT_UNIT_FIELD_NAME);
        $expression = $rule->getProductUnitExpression();
        $path = PriceRuleType::PRODUCT_UNIT_EXPRESSION;
        $nodes = $this->getNodes($expression, $path) ?: [];
        if (!$this->checkNodes($rule, $nodes, $path, $inputName)) {
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
        if (!is_a($relationClassName, ProductUnit::class, true)) {
            $this->addError($path, self::FIELD_ARE_NOT_ALLOWED_MESSAGE, [
                '%fieldName%' => $node->getRelationField(),
                '%inputName%' => $inputName
            ]);
        }
    }

    /**
     * @param PriceRule $rule
     */
    protected function validateQuantity(PriceRule $rule)
    {
        $fieldName = $this->translator->trans(self::QUANTITY_FIELD_NAME);
        $path = PriceRuleType::QUANTITY_EXPRESSION;
        $nodes = $this->getNodes($rule->getQuantityExpression(), $path);
        if (null === $nodes) {
            return;
        }

        $relationNode = null;
        foreach ($nodes as $node) {
            if ($node instanceof RelationNode && $relationNode || $node instanceof NameNode) {
                $this->addError($path, self::TOO_MANY_RELATIONS_MESSAGE, [
                    '%fieldName%' => $fieldName
                ]);

                return;
            } elseif ($node instanceof RelationNode) {
                $relationNode = $node;
            }
        }
        $this->validateIsRelationInRule($rule, $relationNode, $path, $fieldName);
    }

    /**
     * @param array $nodes
     * @param string $path
     * @param string $fieldName
     *
     * @return bool
     */
    protected function validateNodesCount(array $nodes, $path, $fieldName)
    {
        if (count($nodes) !== 1) {
            $this->addError($path, self::ONE_EXPRESSION_ALLOWED_MESSAGE, [
                '%fieldName%' => $fieldName
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param NodeInterface $node
     * @param string $path
     * @param string $fieldName
     * @return bool
     */
    protected function validateIsRelationNode(NodeInterface $node, $path, $fieldName)
    {
        if (!$node instanceof RelationNode) {
            $this->addError($path, self::ONLY_PRICE_RELATION_MESSAGE, [
                '%fieldName%' => $fieldName
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param PriceRule $rule
     * @param RelationNode $node
     * @param string $path
     * @param string $fieldName
     * @return bool
     */
    protected function validateIsRelationInRule(PriceRule $rule, RelationNode $node, $path, $fieldName)
    {
        if (!$this->isRelationInRule($rule, $node)) {
            $this->addError($path, self::RELATION_NOT_IN_RULE_MESSAGE, [
                '%relationName%' => $node->getField(),
                '%fieldName%' => $fieldName
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param string $expression
     * @param string $path
     * @return array|NodeInterface[]|null
     */
    protected function getNodes($expression, $path)
    {
        try {
            $node = $this->parser->parse($expression);
            if ($node) {
                return $node->getNodes();
            }

            return null;
        } catch (SyntaxError $e) {
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
        $ruleNodes = $this->getNodes($rule->getRule(), PriceRuleType::RULE);
        foreach ($ruleNodes as $ruleNode) {
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

    /**
     * @param PriceRule $rule
     * @param array|null $nodes
     * @param string $path
     * @param string $fieldName
     *
     * @return bool
     */
    protected function checkNodes(PriceRule $rule, array $nodes, $path, $fieldName)
    {
        return 0 !== count($nodes) &&
        $this->validateNodesCount($nodes, $path, $fieldName) &&
        $this->validateIsRelationNode($nodes[0], $path, $fieldName) &&
        $this->validateIsRelationInRule($rule, $nodes[0], $path, $fieldName);
    }
}

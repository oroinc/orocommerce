<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validate relations expressions from price lists with price rules.
 */
class PriceRuleRelationExpressionsValidator extends ConstraintValidator
{
    const QUANTITY_FIELD_NAME = 'oro.pricing.pricerule.quantity.label';
    const CURRENCY_FIELD_NAME = 'oro.pricing.pricerule.currency.label';
    const PRODUCT_UNIT_FIELD_NAME = 'oro.pricing.pricerule.product_unit.label';

    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var FieldsProviderInterface
     */
    protected $fieldsProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        ExpressionParser $parser,
        FieldsProviderInterface $fieldsProvider,
        TranslatorInterface $translator
    ) {
        $this->parser = $parser;
        $this->fieldsProvider = $fieldsProvider;

        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     * @param PriceRule $value
     * @param PriceRuleRelationExpressions $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->validateCurrency($value, $constraint);
        $this->validateProductUnit($value, $constraint);
        $this->validateQuantity($value, $constraint);
    }

    protected function validateCurrency(PriceRule $rule, PriceRuleRelationExpressions $constraint)
    {
        $inputName = $this->translator->trans(self::CURRENCY_FIELD_NAME);
        $expression = $rule->getCurrencyExpression();
        $path = PriceRuleType::CURRENCY_EXPRESSION;
        $nodes = $this->getNodes($expression, $path) ?: [];
        if (!$this->checkNodes($rule, $nodes, $path, $inputName, $constraint)) {
            return;
        }

        $node = $nodes[0];
        if ($node->getRelationField() !== 'currency') {
            $this->addError(
                $path,
                $constraint->messageFieldIsNotAllowed,
                [
                    '%fieldName%' => $node->getRelationField(),
                    '%inputName%' => $inputName,
                ]
            );
        }
    }

    /**
     * @param string $expression
     * @param string $path
     * @return array|Node\NodeInterface[]|null
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
            // {@see Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntax} should handle this case.
            return null;
        }
    }

    /**
     * @param string $path
     * @param string $message
     * @param array $params
     */
    protected function addError($path, $message, array $params = [])
    {
        $this->context->buildViolation($message, $params)
            ->atPath($path)
            ->addViolation();
    }

    /**
     * @param PriceRule $rule
     * @param array|null $nodes
     * @param string $path
     * @param string $fieldName
     * @param PriceRuleRelationExpressions $constraint
     * @return bool
     */
    protected function checkNodes(
        PriceRule $rule,
        array $nodes,
        $path,
        $fieldName,
        PriceRuleRelationExpressions $constraint
    ) {
        return 0 !== count($nodes) &&
        $this->validateIsFieldExistInRelationNode($nodes[0], $path, $fieldName, $constraint) &&
        $this->validateNodesCount($nodes, $path, $fieldName, $constraint) &&
        $this->validateIsRelationNode($nodes[0], $path, $fieldName, $constraint) &&
        $this->validateIsRelationInRule($rule, $nodes[0], $path, $fieldName, $constraint);
    }

    /**
     * @param $node
     * @return bool
     */
    protected function validateIsFieldExistInRelationNode($node, $path, $inputName, $constraint)
    {
        if ($node instanceof Node\RelationNode) {
            $numericOnly = false;
            $withRelations = true;
            $fields = $this->fieldsProvider->getDetailedFieldsInformation(
                $node->getContainer(),
                $numericOnly,
                $withRelations
            );

            if (!array_key_exists($node->getField(), $fields)) {
                $this->addError(
                    $path,
                    $constraint->messageFieldIsNotAllowed,
                    [
                        '%fieldName%' => $node->getField(),
                        '%inputName%' => $inputName,
                    ]
                );
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $nodes
     * @param string $path
     * @param string $fieldName
     * @param PriceRuleRelationExpressions $constraint
     * @return bool
     */
    protected function validateNodesCount(array $nodes, $path, $fieldName, PriceRuleRelationExpressions $constraint)
    {
        if (count($nodes) !== 1) {
            $this->addError(
                $path,
                $constraint->messageOnlyOneExpressionAllowed,
                [
                    '%fieldName%' => $fieldName,
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * @param Node\NodeInterface $node
     * @param string $path
     * @param string $fieldName
     * @param PriceRuleRelationExpressions $constraint
     * @return bool
     */
    protected function validateIsRelationNode(
        Node\NodeInterface $node,
        $path,
        $fieldName,
        PriceRuleRelationExpressions $constraint
    ) {
        if (!$node instanceof Node\RelationNode) {
            $this->addError(
                $path,
                $constraint->messageOnlyPriceRelationAllowed,
                [
                    '%fieldName%' => $fieldName,
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * @param PriceRule $rule
     * @param Node\RelationNode $node
     * @param string $path
     * @param string $fieldName
     * @param PriceRuleRelationExpressions $constraint
     * @return bool
     */
    protected function validateIsRelationInRule(
        PriceRule $rule,
        Node\RelationNode $node,
        $path,
        $fieldName,
        PriceRuleRelationExpressions $constraint
    ) {
        try {
            $relationClassName = $this->fieldsProvider->getRealClassName($node->getContainer(), $node->getField());
        } catch (\Exception $e) {
            return true;
        }
        if (is_a($relationClassName, BaseProductPrice::class, true) && !$this->isRelationInRule($rule, $node)) {
            $this->addError(
                $path,
                $constraint->messageRelationNotUsedInRule,
                [
                    '%relationName%' => $node->getField(),
                    '%fieldName%' => $fieldName,
                ]
            );

            return false;
        }

        return true;
    }

    /**
     * @param PriceRule $rule
     * @param Node\RelationNode $node
     * @return bool
     */
    public function isRelationInRule(PriceRule $rule, Node\RelationNode $node)
    {
        $ruleNodes = $this->getNodes($rule->getRule(), PriceRuleType::RULE);
        if (null === $ruleNodes) {
            return false;
        }
        foreach ($ruleNodes as $ruleNode) {
            if ($ruleNode instanceof Node\RelationNode && $this->isSameRelation($ruleNode, $node)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Node\RelationNode $node
     * @param Node\RelationNode $other
     * @return bool
     */
    public function isSameRelation(Node\RelationNode $node, Node\RelationNode $other)
    {
        return $node->getContainer() === $other->getContainer() &&
        $node->getContainerId() == $other->getContainerId() &&
        $node->getField() === $other->getField();
    }

    protected function validateProductUnit(PriceRule $rule, PriceRuleRelationExpressions $constraint)
    {
        $inputName = $this->translator->trans(self::PRODUCT_UNIT_FIELD_NAME);
        $expression = $rule->getProductUnitExpression();
        $path = PriceRuleType::PRODUCT_UNIT_EXPRESSION;
        $nodes = $this->getNodes($expression, $path) ?: [];
        if (!$this->checkNodes($rule, $nodes, $path, $inputName, $constraint)) {
            return;
        }

        /** @var Node\RelationNode $node */
        $node = $nodes[0];
        $relationContainerClassName = $this->fieldsProvider
            ->getRealClassName($node->getContainer(), $node->getField());

        $relationClassName = null;
        if ($this->fieldsProvider->isRelation($relationContainerClassName, $node->getRelationField())) {
            $relationClassName = $this->fieldsProvider
                ->getRealClassName(
                    $relationContainerClassName,
                    $node->getRelationField()
                );
        }
        if (!is_a($relationClassName, ProductUnit::class, true)) {
            $this->addError(
                $path,
                $constraint->messageFieldIsNotAllowed,
                [
                    '%fieldName%' => $node->getRelationField(),
                    '%inputName%' => $inputName,
                ]
            );
        }
    }

    protected function validateQuantity(PriceRule $rule, PriceRuleRelationExpressions $constraint)
    {
        $fieldName = $this->translator->trans(self::QUANTITY_FIELD_NAME);
        $path = PriceRuleType::QUANTITY_EXPRESSION;
        $nodes = $this->getNodes($rule->getQuantityExpression(), $path);
        if (null === $nodes) {
            return;
        }

        $relationNode = null;
        foreach ($nodes as $node) {
            if (($node instanceof Node\RelationNode && $relationNode) || $node instanceof Node\NameNode) {
                $this->addError(
                    $path,
                    $constraint->messageTooManyRelations,
                    [
                        '%fieldName%' => $fieldName,
                    ]
                );

                return;
            } elseif ($node instanceof Node\RelationNode) {
                $relationNode = $node;
            }
        }
        $this->validateIsRelationInRule($rule, $relationNode, $path, $fieldName, $constraint);
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Expression\BinaryNode;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Oro\Bundle\PricingBundle\Expression\ValueNode;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PriceRuleExpressionValidator extends ConstraintValidator
{
    /**
     * @var ExpressionParser
     */
    protected $parser;

    /**
     * @var PriceRuleFieldsProvider
     */
    protected $priceRuleFieldsProvider;

    /**
     * @var ExpressionPreprocessorInterface
     */
    protected $preprocessor;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ExpressionParser $parser
     * @param ExpressionPreprocessorInterface $preprocessor
     * @param PriceRuleFieldsProvider $priceRuleFieldsProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ExpressionParser $parser,
        ExpressionPreprocessorInterface $preprocessor,
        PriceRuleFieldsProvider $priceRuleFieldsProvider,
        TranslatorInterface $translator
    ) {
        $this->parser = $parser;
        $this->priceRuleFieldsProvider = $priceRuleFieldsProvider;
        $this->preprocessor = $preprocessor;
        $this->translator = $translator;
    }

    /**
     * @param string $value
     * @param PriceRuleExpression $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value === null || $value === '') {
            return;
        }
        try {
            $value = $this->preprocessor->process($value);
            $rootNode = $this->parser->parse($value);

            $this->validateSupportedFields($rootNode, $constraint);
            $this->validateDivisionByZero($rootNode, $constraint);
        } catch (SyntaxError $ex) {
            $this->context->addViolation($ex->getMessage());
        }
    }

    /**
     * @param PriceRuleExpression $constraint
     * @return array
     */
    protected function getErrorData(PriceRuleExpression $constraint)
    {
        if ($constraint->fieldLabel === null) {
            $message = $constraint->message;
            $params = [];
        } else {
            $inputName = $this->translator->trans($constraint->fieldLabel);
            $message = $constraint->messageAs;
            $params = ['%inputName%' => $inputName];
        }

        return [$message, $params];
    }

    /**
     * @param PriceRuleExpression|Constraint $constraint
     * @param string $class
     * @return array
     */
    protected function getSupportedFields(Constraint $constraint, $class)
    {
        $supportedFields = $this->priceRuleFieldsProvider->getFields(
            $class,
            $constraint->numericOnly,
            $constraint->withRelations
        );
        if (array_key_exists($class, $constraint->allowedFields)) {
            $supportedFields = array_merge($supportedFields, $constraint->allowedFields[$class]);
        }
        // Add possibility lexemes without fields
        $supportedFields[] = null;

        return $supportedFields;
    }

    /**
     * @param NodeInterface $rootNode
     * @param PriceRuleExpression $constraint
     */
    protected function validateSupportedFields(NodeInterface $rootNode, PriceRuleExpression $constraint)
    {
        $unsupportedFields = [];
        $lexemesInfo = $this->parser->getUsedLexemesByNode($rootNode);
        foreach ($lexemesInfo as $class => $fields) {
            try {
                $supportedFields = $this->getSupportedFields($constraint, $class);
                $unsupportedFields = array_merge($unsupportedFields, array_diff($fields, $supportedFields));
            } catch (\InvalidArgumentException $ex) {
                if (strpos($class, '::') !== false) {
                    $relationInfo = explode('::', $class);
                    $unsupportedFields[] = $relationInfo[1];
                }
            }
        }
        if (count($unsupportedFields) > 0) {
            list($message, $parameters) = $this->getErrorData($constraint);

            foreach ($unsupportedFields as $invalidField) {
                $this->context->addViolation($message, array_merge($parameters, [
                    '%fieldName%' => $invalidField
                ]));
            }
        }
    }

    /**
     * @param NodeInterface $rootNode
     * @param PriceRuleExpression $constraint
     */
    protected function validateDivisionByZero(NodeInterface $rootNode, PriceRuleExpression $constraint)
    {
        foreach ($rootNode->getNodes() as $node) {
            if ($node instanceof BinaryNode) {
                $right = $node->getRight();
                if ($node->getOperation() === '/' && $right instanceof ValueNode && $right->getValue() == 0.0) {
                    $this->context->addViolation($constraint->divisionByZeroMessage);
                }
            }
        }
    }
}

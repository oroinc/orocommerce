<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
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
     * @var FieldsProviderInterface
     */
    protected $fieldsProvider;

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
     * @param FieldsProviderInterface $fieldsProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ExpressionParser $parser,
        ExpressionPreprocessorInterface $preprocessor,
        FieldsProviderInterface $fieldsProvider,
        TranslatorInterface $translator
    ) {
        $this->parser = $parser;
        $this->fieldsProvider = $fieldsProvider;
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
        $supportedFields = $this->fieldsProvider->getFields(
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
     * @param Node\NodeInterface $rootNode
     * @param PriceRuleExpression $constraint
     */
    protected function validateSupportedFields(Node\NodeInterface $rootNode, PriceRuleExpression $constraint)
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
     * @param Node\NodeInterface $rootNode
     * @param PriceRuleExpression $constraint
     */
    protected function validateDivisionByZero(Node\NodeInterface $rootNode, PriceRuleExpression $constraint)
    {
        foreach ($rootNode->getNodes() as $node) {
            if ($node instanceof Node\BinaryNode) {
                $right = $node->getRight();
                if ($node->getOperation() === '/' && $right instanceof Node\ValueNode && $right->getValue() == 0.0) {
                    $this->context->addViolation($constraint->divisionByZeroMessage);
                }
            }
        }
    }
}

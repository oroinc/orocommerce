<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This validator is used to check whether a string represents a valid expression.
 */
class ExpressionValidator extends ConstraintValidator
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
     * @param Expression $constraint
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
            // {@see Oro\Bundle\RuleBundle\Validator\Constraints\ExpressionLanguageSyntax} should handle this case.
            return;
        }
    }

    /**
     * @param Expression $constraint
     * @return array
     */
    protected function getErrorData(Expression $constraint)
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
     * @param Expression|Constraint $constraint
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

    protected function validateSupportedFields(Node\NodeInterface $rootNode, Expression $constraint)
    {
        $unsupportedFields = [];
        $lexemesInfo = $this->parser->getUsedLexemesByNode($rootNode);
        foreach ($lexemesInfo as $class => $fields) {
            try {
                $supportedFields = $this->getSupportedFields($constraint, $class);
                $unsupportedFields = array_merge($unsupportedFields, array_diff($fields, $supportedFields));
            } catch (\InvalidArgumentException $ex) {
                if (str_contains($class, '::')) {
                    $relationInfo = explode('::', $class);
                    $unsupportedFields[] = $relationInfo[1];
                }
            }
        }
        if (count($unsupportedFields) > 0) {
            [$message, $parameters] = $this->getErrorData($constraint);

            foreach ($unsupportedFields as $invalidField) {
                $this->context->addViolation($message, array_merge($parameters, [
                    '%fieldName%' => $invalidField
                ]));
            }
        }
    }

    protected function validateDivisionByZero(Node\NodeInterface $rootNode, Expression $constraint)
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

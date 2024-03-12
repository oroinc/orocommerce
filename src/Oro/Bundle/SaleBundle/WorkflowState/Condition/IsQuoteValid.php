<?php

namespace Oro\Bundle\SaleBundle\WorkflowState\Condition;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Workflow condition used to validate {@see Quote}.
 *
 * Usage:
 *
 * @is_quote_valid:
 *      quote: $quote
 *      validationGroups: ['GroupA', 'GroupB']
 */
class IsQuoteValid extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /** @var Quote */
    private $quote;

    /** @var array */
    private $validationGroups;

    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('quote', $options)) {
            $this->quote = $options['quote'];
        }

        if (!$this->quote) {
            throw new InvalidArgumentException('Missing "quote" option');
        }

        $this->validationGroups = $options['validationGroups'] ?? [];

        return $this;
    }

    public function getName()
    {
        return 'is_quote_valid';
    }

    protected function doEvaluate($context)
    {
        return $this->isConditionAllowed($context);
    }

    protected function isConditionAllowed($context)
    {
        /** @var Quote $quote */
        $quote = $this->resolveValue($context, $this->quote, false);

        if (!$quote instanceof Quote) {
            return false;
        }

        /** @var array $validationGroups */
        $validationGroups = $this->resolveValue($context, $this->validationGroups, false);

        if (!is_array($validationGroups)) {
            return false;
        }

        $constraintViolationList = $this->validator->validate(
            $quote,
            null,
            ValidationGroupUtils::resolveValidationGroups($validationGroups)
        );

        return $constraintViolationList->count() === 0;
    }

    public function toArray()
    {
        return $this->convertToArray([$this->quote, $this->validationGroups]);
    }

    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->quote, $this->validationGroups], $factoryAccessor);
    }
}

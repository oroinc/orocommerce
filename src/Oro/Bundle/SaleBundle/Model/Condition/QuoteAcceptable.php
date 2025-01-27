<?php

namespace Oro\Bundle\SaleBundle\Model\Condition;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Validator\Constraints\QuoteAcceptable as QuoteAcceptableConstraint;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Check that quote is acceptable
 *
 * Usage:
 * @quote_acceptable:
 *      quote: $quote  # Quote or QuoteDemand
 *      default: false # bool - returns if quote is not provided
 */
class QuoteAcceptable extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    /** @var Quote */
    protected $quote;

    /** @var bool */
    protected $default = false;

    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    #[\Override]
    public function getName()
    {
        return 'quote_acceptable';
    }

    #[\Override]
    public function initialize(array $options)
    {
        $quote = array_shift($options);
        if (!$quote instanceof PropertyPathInterface) {
            throw new InvalidArgumentException('First option should be valid property definition.');
        }
        $this->quote = $quote;

        $default = array_shift($options);
        if (is_bool($default) || $default instanceof PropertyPathInterface) {
            $this->default = $default;
        }

        return $this;
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        $constraint = new QuoteAcceptableConstraint();
        $constraint->default = $this->contextAccessor->getValue($context, $this->default);

        $violationList = $this->validator->validate(
            $this->resolveValue($context, $this->quote, null),
            $constraint
        );

        return $violationList->count() === 0;
    }

    #[\Override]
    protected function getMessage()
    {
        return 'oro.frontend.sale.message.quote.not_available';
    }

    #[\Override]
    protected function getMessageParameters($context)
    {
        $quote = $this->getQuote($context);

        return ['%qid%' => $quote ? $quote->getQid() : 0];
    }

    protected function getQuote(mixed $context): ?Quote
    {
        $quote = $this->resolveValue($context, $this->quote, false);
        if ($quote instanceof QuoteDemand) {
            $quote = $quote->getQuote();
        }

        return $quote instanceof Quote ? $quote : null;
    }
}

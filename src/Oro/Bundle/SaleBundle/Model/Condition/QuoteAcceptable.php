<?php

namespace Oro\Bundle\SaleBundle\Model\Condition;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

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

    const NAME = 'quote_acceptable';

    /** @var Quote */
    protected $quote;

    /** @var bool */
    protected $default = false;

    #[\Override]
    public function getName()
    {
        return self::NAME;
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
        $quote = $this->getQuote($context);
        $default = $this->contextAccessor->getValue($context, $this->default);

        return $quote ? $quote->isAcceptable() : $default;
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

    /**
     * @param mixed $context
     * @return null|Quote
     */
    protected function getQuote($context)
    {
        $quote = $this->resolveValue($context, $this->quote, false);

        if ($quote instanceof QuoteDemand) {
            $quote = $quote->getQuote();
        }

        return $quote instanceof Quote ? $quote : null;
    }
}

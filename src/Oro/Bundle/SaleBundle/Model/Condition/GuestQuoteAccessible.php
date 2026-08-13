<?php

namespace Oro\Bundle\SaleBundle\Model\Condition;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProviderInterface;
use Oro\Component\Action\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Check that quote is accessible by guest link on storefront.
 */
class GuestQuoteAccessible extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    public const NAME = 'guest_quote_accessible';

    /** @var PropertyPathInterface */
    protected $quote;

    /** @var GuestQuoteAccessProviderInterface */
    private $guestQuoteAccessProvider;

    public function __construct(GuestQuoteAccessProviderInterface $guestQuoteAccessProvider)
    {
        $this->guestQuoteAccessProvider = $guestQuoteAccessProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $quote = array_shift($options);

        if (!$quote instanceof PropertyPathInterface) {
            throw new InvalidArgumentException('First option should be valid property definition.');
        }

        $this->quote = $quote;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $quote = $this->resolveValue($context, $this->quote, false);

        return $quote instanceof Quote && $this->guestQuoteAccessProvider->isGranted($quote);
    }
}

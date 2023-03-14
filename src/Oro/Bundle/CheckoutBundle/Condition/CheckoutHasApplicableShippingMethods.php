<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\AvailableShippingMethodCheckerInterface;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check applicable shipping methods for a specific checkout.
 * Usage:
 * @checkout_has_applicable_shipping_methods:
 *      checkout: ~
 */
class CheckoutHasApplicableShippingMethods extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private AvailableShippingMethodCheckerInterface $availableShippingMethodChecker;
    private mixed $checkout = null;

    public function __construct(AvailableShippingMethodCheckerInterface $availableShippingMethodChecker)
    {
        $this->availableShippingMethodChecker = $availableShippingMethodChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (\array_key_exists('checkout', $options)) {
            $this->checkout = $options['checkout'];
        } elseif (\array_key_exists(0, $options)) {
            $this->checkout = $options[0];
        }

        if (!$this->checkout) {
            throw new InvalidArgumentException('Missing "checkout" option');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'checkout_has_applicable_shipping_methods';
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        /** @var Checkout $checkout */
        $checkout = $this->resolveValue($context, $this->checkout, false);
        if (null === $checkout) {
            return false;
        }

        return $this->availableShippingMethodChecker->hasAvailableShippingMethods($checkout);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->checkout]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->checkout], $factoryAccessor);
    }
}

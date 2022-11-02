<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

class CheckoutHasApplicableShippingMethods extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'checkout_has_applicable_shipping_methods';

    /**
     * @var CheckoutShippingMethodsProviderInterface
     */
    private $checkoutShippingMethodsProvider;

    /**
     * @var mixed
     */
    private $checkout;

    public function __construct(CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider)
    {
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('checkout', $options)) {
            $this->checkout = $options['checkout'];
        } elseif (array_key_exists(0, $options)) {
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
        return self::NAME;
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

        return false === $this->checkoutShippingMethodsProvider
            ->getApplicableMethodsViews($checkout)
            ->isEmpty();
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

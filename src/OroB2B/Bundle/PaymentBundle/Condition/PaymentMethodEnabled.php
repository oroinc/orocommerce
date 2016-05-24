<?php

namespace OroB2B\Bundle\PaymentBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

/**
 * Check payment method enabled
 * Usage:
 * @payment_method_enabled: 'payment_term'
 */
class PaymentMethodEnabled extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'payment_method_enabled';

    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /** @var string */
    protected $paymentMethod;

    /**
     * @param PaymentMethodRegistry $paymentMethodRegistry
     */
    public function __construct(PaymentMethodRegistry $paymentMethodRegistry)
    {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /** {@inheritdoc} */
    public function initialize(array $options)
    {
        $optionsCount = count($options);
        if (1 !== $optionsCount) {
            throw new InvalidArgumentException(sprintf('Options must have 1 element, but %d given', $optionsCount));
        }

        $this->paymentMethod = reset($options);

        return $this;
    }

    /** {@inheritdoc} */
    protected function isConditionAllowed($context)
    {
        $paymentMethod = $this->resolveValue($context, $this->paymentMethod, false);

        try {
            return $this->paymentMethodRegistry->getPaymentMethod($paymentMethod)->isEnabled();
        } catch (\InvalidArgumentException $e) {
        }

        return false;
    }

    /** {@inheritdoc} */
    public function toArray()
    {
        return $this->convertToArray([$this->paymentMethod]);
    }

    /** {@inheritdoc} */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->paymentMethod], $factoryAccessor);
    }
}

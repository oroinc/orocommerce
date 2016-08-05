<?php

namespace OroB2B\Bundle\PaymentBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRequiresVerificationInterface;

/**
 * Check payment method requires verification after checkout page refreshed
 * Usage:
 * @payment_method_requires_verification:
 *      payment_method: 'payment_term'
 */
class PaymentMethodRequiresVerification extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'payment_method_requires_verification';

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
        if (empty($options['payment_method']) && empty($options['action'])) {
            throw new \InvalidArgumentException();
        }

        $this->paymentMethod = $options['payment_method'];

        return $this;
    }

    /** {@inheritdoc} */
    protected function isConditionAllowed($context)
    {
        $paymentMethodName = $this->resolveValue($context, $this->paymentMethod, false);
        $paymentMethod = $this->paymentMethodRegistry->getPaymentMethod($paymentMethodName);

        if($paymentMethod instanceof PaymentMethodRequiresVerificationInterface) {
            return $paymentMethod->requiresVerification();
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

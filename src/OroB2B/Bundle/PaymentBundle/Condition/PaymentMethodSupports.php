<?php

namespace OroB2B\Bundle\PaymentBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

/**
 * Check payment method supports action
 * Usage:
 * @payment_method_supports:
 *      payment_method: 'payment_term'
 *      action: 'validate'
 */
class PaymentMethodSupports extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'payment_method_supports';

    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /** @var string */
    protected $paymentMethod;

    /** @var string */
    protected $actionName;

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
        $this->actionName = $options['action'];

        return $this;
    }

    /** {@inheritdoc} */
    protected function isConditionAllowed($context)
    {
        $paymentMethod = $this->resolveValue($context, $this->paymentMethod, false);
        $actionName = $this->resolveValue($context, $this->actionName, false);

        try {
            return $this->paymentMethodRegistry->getPaymentMethod($paymentMethod)->supports($actionName);
        } catch (\InvalidArgumentException $e) {
        }

        return false;
    }

    /** {@inheritdoc} */
    public function toArray()
    {
        return $this->convertToArray([$this->paymentMethod, $this->actionName]);
    }

    /** {@inheritdoc} */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->paymentMethod, $this->actionName], $factoryAccessor);
    }
}

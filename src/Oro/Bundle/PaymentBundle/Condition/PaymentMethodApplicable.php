<?php

namespace Oro\Bundle\PaymentBundle\Condition;

use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check payment method enabled and applicable
 * Usage:
 * @payment_method_applicable:
 *      payment_method: 'payment_method_name'
 *      context: ~
 */
class PaymentMethodApplicable extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'payment_method_applicable';

    /** @var object */
    protected $context;

    /** @var string */
    protected $paymentMethod;

    /** @var ApplicablePaymentMethodsProvider */
    protected $paymentMethodProvider;

    public function __construct(ApplicablePaymentMethodsProvider $methodProvider)
    {
        $this->paymentMethodProvider = $methodProvider;
    }

    /** {@inheritdoc} */
    public function initialize(array $options)
    {
        if (array_key_exists('payment_method', $options)) {
            $this->paymentMethod = $options['payment_method'];
        } elseif (array_key_exists(0, $options)) {
            $this->paymentMethod = $options[0];
        }

        if (array_key_exists('context', $options)) {
            $this->context = $options['context'];
        } elseif (array_key_exists(1, $options)) {
            $this->context = $options[1];
        }

        if (!$this->paymentMethod) {
            throw new InvalidArgumentException('Missing "payment_method" option');
        }

        if (!$this->context) {
            throw new InvalidArgumentException('Missing "context" option');
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

    /** {@inheritdoc} */
    protected function isConditionAllowed($context)
    {
        $paymentMethodName = $this->resolveValue($context, $this->paymentMethod, false);
        $paymentContext = $this->resolveValue($context, $this->context, false);
        $methods = $this->paymentMethodProvider->getApplicablePaymentMethods($paymentContext);
        foreach ($methods as $method) {
            if ($method->getIdentifier() === $paymentMethodName) {
                return true;
            }
        }
        return false;
    }

    /** {@inheritdoc} */
    public function toArray()
    {
        return $this->convertToArray([$this->paymentMethod, $this->context]);
    }

    /** {@inheritdoc} */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->paymentMethod, $this->context], $factoryAccessor);
    }
}

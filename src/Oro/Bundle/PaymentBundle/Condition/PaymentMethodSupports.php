<?php

namespace Oro\Bundle\PaymentBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;

/**
 * Check payment method supports action
 * Usage:
 * @payment_method_supports:
 *      payment_method: 'payment_method_name'
 *      action: 'validate'
 */
class PaymentMethodSupports extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'payment_method_supports';

    /** @var PaymentMethodProvidersRegistry */
    protected $paymentMethodRegistry;

    /** @var string */
    protected $paymentMethod;

    /** @var string */
    protected $actionName;

    /**
     * @param PaymentMethodProvidersRegistry $paymentMethodRegistry
     */
    public function __construct(PaymentMethodProvidersRegistry $paymentMethodRegistry)
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
            foreach ($this->paymentMethodRegistry->getPaymentMethodProviders() as $provider) {
                if ($provider->hasPaymentMethod($paymentMethod)) {
                    return $provider->getPaymentMethod($paymentMethod)->supports($actionName);
                }
            }
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

<?php

namespace Oro\Bundle\PaymentBundle\Condition;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

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

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @var string
     */
    protected $actionName;

    public function __construct(PaymentMethodProviderInterface $paymentMethodProvider)
    {
        $this->paymentMethodProvider = $paymentMethodProvider;
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

        if ($this->paymentMethodProvider->hasPaymentMethod($paymentMethod)) {
            return $this->paymentMethodProvider
                ->getPaymentMethod($paymentMethod)
                ->supports($actionName);
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

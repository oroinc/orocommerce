<?php

namespace Oro\Bundle\PaymentBundle\Condition;

use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check applicable payment methods
 * Usage:
 * @has_applicable_payment_methods:
 *      context: ~
 */
class HasApplicablePaymentMethods extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    public const NAME = 'has_applicable_payment_methods';

    /** @var ApplicablePaymentMethodsProvider */
    protected $paymentMethodProvider;

    /** @var object */
    protected $context;

    public function __construct(ApplicablePaymentMethodsProvider $methodProvider)
    {
        $this->paymentMethodProvider = $methodProvider;
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (array_key_exists('context', $options)) {
            $this->context = $options['context'];
        } elseif (array_key_exists(0, $options)) {
            $this->context = $options[0];
        }

        if (!$this->context) {
            throw new InvalidArgumentException('Missing "context" option');
        }

        return $this;
    }

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }

    #[\Override]
    protected function isConditionAllowed($context)
    {
        $paymentContext = $this->resolveValue($context, $this->context, false);
        $methods = $this->paymentMethodProvider->getApplicablePaymentMethods($paymentContext);
        return count($methods) > 0;
    }

    #[\Override]
    public function toArray()
    {
        return $this->convertToArray([$this->context]);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->context], $factoryAccessor);
    }
}

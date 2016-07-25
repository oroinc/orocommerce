<?php

namespace OroB2B\Bundle\PaymentBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentContextProvider;

/**
 * Check applicable payment methods
 * Usage:
 * @has_applicable_payment_methods:
 *      entity: ~
 */
class HasApplicablePaymentMethods extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'has_applicable_payment_methods';

    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /** @var PaymentContextProvider */
    protected $paymentContextProvider;

    /** @var object */
    protected $entity;

    /**
     * @param PaymentMethodRegistry $paymentMethodRegistry
     * @param PaymentContextProvider $paymentContextProvider
     */
    public function __construct(
        PaymentMethodRegistry $paymentMethodRegistry,
        PaymentContextProvider $paymentContextProvider
    ) {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
        $this->paymentContextProvider = $paymentContextProvider;
    }

    /** {@inheritdoc} */
    public function initialize(array $options)
    {
        if (array_key_exists('entity', $options)) {
            $this->entity = $options['entity'];
        } elseif (array_key_exists(0, $options)) {
            $this->entity = $options[0];
        }

        if (!$this->entity) {
            throw new InvalidArgumentException('Missing "entity" option');
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
        $entity = $this->resolveValue($context, $this->entity, false);
        $paymentContext = $this->paymentContextProvider->processContext($context, $entity);

        $paymentMethods = $this->paymentMethodRegistry->getPaymentMethods();
        foreach ($paymentMethods as $paymentMethod) {
            if (!$paymentMethod->isEnabled()) {
                continue;
            }

            if (!$paymentMethod->isApplicable($paymentContext)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /** {@inheritdoc} */
    public function toArray()
    {
        return $this->convertToArray([$this->entity]);
    }

    /** {@inheritdoc} */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->entity], $factoryAccessor);
    }
}

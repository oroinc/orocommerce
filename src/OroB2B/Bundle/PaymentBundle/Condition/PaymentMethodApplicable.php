<?php

namespace OroB2B\Bundle\PaymentBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\AddressExtractor;

/**
 * Check payment method enabled and applicable
 * Usage:
 * @payment_method_applicable:
 *      payment_method: 'payment_term'
 *      entity: []
 */
class PaymentMethodApplicable extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'payment_method_applicable';

    /** @var object */
    protected $entity;

    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /** @var AddressExtractor */
    protected $addressExtractor;

    /** @var string */
    protected $paymentMethod;

    /**
     * @param PaymentMethodRegistry $paymentMethodRegistry
     * @param AddressExtractor $addressExtractor
     */
    public function __construct(PaymentMethodRegistry $paymentMethodRegistry, AddressExtractor $addressExtractor)
    {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
        $this->addressExtractor = $addressExtractor;
    }

    /** {@inheritdoc} */
    public function initialize(array $options)
    {
        if (array_key_exists('payment_method', $options)) {
            $this->paymentMethod = $options['payment_method'];
        } elseif (array_key_exists(0, $options)) {
            $this->paymentMethod = $options[0];
        }

        if (array_key_exists('entity', $options)) {
            $this->entity = $options['entity'];
        } elseif (array_key_exists(1, $options)) {
            $this->entity = $options[1];
        }

        if (!$this->paymentMethod) {
            throw new InvalidArgumentException('Missing "payment_method" option');
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
        $paymentMethodName = $this->resolveValue($context, $this->paymentMethod, false);

        try {
            $paymentMethod = $this->paymentMethodRegistry->getPaymentMethod($paymentMethodName);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        if (!$paymentMethod->isEnabled()) {
            return false;
        }

        $entity = $this->resolveValue($context, $this->entity, false);

        return $paymentMethod
            ->isApplicable(['entity' => $entity, 'country' => $this->addressExtractor->getCountryIso2($entity)]);
    }

    /** {@inheritdoc} */
    public function toArray()
    {
        return $this->convertToArray([$this->paymentMethod, $this->entity]);
    }

    /** {@inheritdoc} */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->paymentMethod, $this->entity], $factoryAccessor);
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\ShippingRulesProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check applicable shipping methods
 * Usage:
 * @has_applicable_shipping_methods:
 *      entity: ~
 */
class HasApplicableShippingMethods extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'has_applicable_shipping_methods';

    /** @var ShippingMethodRegistry */
    protected $shippingMethodRegistry;

    /** ShippingRulesProvider */
    protected $shippingRulesProvider;

    /** ShippingContextProviderFactory */
    protected $shippingContextProviderFactory;

    /** @var Checkout */
    protected $entity;

    /**
     * @param ShippingMethodRegistry $shippingMethodRegistry
     * @param ShippingRulesProvider $shippingRulesProvider
     * @param ShippingContextProviderFactory $shippingContextProviderFactory
     */
    public function __construct(
        ShippingMethodRegistry $shippingMethodRegistry,
        ShippingRulesProvider $shippingRulesProvider,
        ShippingContextProviderFactory $shippingContextProviderFactory
    ) {
        $this->shippingMethodRegistry = $shippingMethodRegistry;
        $this->shippingRulesProvider = $shippingRulesProvider;
        $this->shippingContextProviderFactory = $shippingContextProviderFactory;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $result = false;
        /** @var Checkout $entity */
        $entity = $this->resolveValue($context, $this->entity, false);

        $rules = [];
        if (null !==$entity) {
            $shippingContext = $this->shippingContextProviderFactory->create($entity);
            $rules = $this->shippingRulesProvider->getApplicableShippingRules($shippingContext);
        }
        if (0 !== count($rules)) {
            $result = true;
            /** @var ShippingRule $rule */
            foreach ($rules as $rule) {
                foreach ($rule->getMethodConfigs() as $config) {
                    $method = $this->shippingMethodRegistry->getShippingMethod($config->getMethod());
                    if (null === $method) {
                        $result = false;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->entity], $factoryAccessor);
    }
}

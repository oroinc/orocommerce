<?php

namespace OroB2B\Bundle\CheckoutBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Factory\ShippingContextProviderFactory;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

/**
 * Check shipping method supports
 * Usage:
 * @shipping_method_supports:
 *      entity: $checkout
 *      shipping_rule_config_id: $shipping_rule_config
 */
class ShippingMethodSupports extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'shipping_method_supports';

    /** @var ShippingMethodRegistry */
    protected $shippingMethodRegistry;

    /** ShippingRulesProvider */
    protected $shippingRulesProvider;

    /** ShippingContextProviderFactory */
    protected $shippingContextProviderFactory;

    /** @var Checkout */
    protected $entity;

    /** @var  ShippingRuleConfiguration */
    protected $shippingRuleConfig;

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
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('entity', $options)) {
            $this->entity = $options['entity'];
        }
        if (array_key_exists('shipping_rule_config', $options)) {
            $this->shippingRuleConfig = $options['shipping_rule_config'];
        }
        if (!$this->entity && array_key_exists(0, $options)) {
            $this->entity = $options[0];
        }
        if (!$this->shippingRuleConfig && array_key_exists(1, $options)) {
            $this->shippingRuleConfig = $options[1];
        }

        if (!$this->entity) {
            throw new InvalidArgumentException('Missing "entity" option');
        }

        if (!$this->shippingRuleConfig) {
            throw new InvalidArgumentException('Missing "shipping_rule_config" option');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $result = false;
        /** @var Checkout $entity */
        $entity = $this->resolveValue($context, $this->entity, false);
        $shippingRuleConfig = $this->resolveValue($context, $this->shippingRuleConfig, false);

        $rules = [];
        if (null !==$entity) {
            $shippingContext = $this->shippingContextProviderFactory->create($entity);
            $rules = $this->shippingRulesProvider->getApplicableShippingRules($shippingContext);
        }
        if (0 !== count($rules)) {
            foreach ($rules as $rule) {
                foreach ($rule->getConfigurations() as $config) {
                    if ($config === $shippingRuleConfig) {
                        $result = $this->evaluateShippingConfiguration($entity, $config);
                    }
                    if ($result) {
                        return $result;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param Checkout $entity
     * @param ShippingRuleConfiguration $config
     * @return bool
     */
    protected function evaluateShippingConfiguration(Checkout $entity, ShippingRuleConfiguration $config)
    {
        if ($config->getMethod() === $entity->getShippingMethod()) {
            $method = $this->shippingMethodRegistry->getShippingMethod($config->getMethod());
            $types = $method->getShippingTypes();
            if (!$entity->getShippingMethodType() && 0 === count($types)) {
                return true;
            } elseif (in_array($entity->getShippingMethodType(), $types, true)) {
                return true;
            }
        }
        
        return false;
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

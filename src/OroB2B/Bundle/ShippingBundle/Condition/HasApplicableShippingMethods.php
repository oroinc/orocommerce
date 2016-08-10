<?php

namespace OroB2B\Bundle\ShippingBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;

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

    /** @var object */
    protected $entity;

    /**
     * @param ShippingMethodRegistry $shippingMethodRegistry
     * @param ShippingRulesProvider $shippingRulesProvider
     */
    public function __construct(
        ShippingMethodRegistry $shippingMethodRegistry,
        ShippingRulesProvider $shippingRulesProvider
    ) {
        $this->shippingMethodRegistry = $shippingMethodRegistry;
        $this->shippingRulesProvider = $shippingRulesProvider;
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
        $result = false;
        /** @var Checkout $entity */
        $entity = $this->resolveValue($context, $this->entity, false);

        if (!empty($entity)) {
            $context = [
                'checkout' => $entity,
                'shippingAddress' => $entity->getShippingAddress(),
                'currency' => $entity->getCurrency(),
            ];
            $sourceEntity = $entity->getSourceEntity();
            if (!empty($sourceEntity)) {
                $context['line_items'] = $sourceEntity->getLineItems();
            }
            $shippingContext = new ShippingContextProvider($context);
            $rules = $this->shippingRulesProvider->getApplicableShippingRules($shippingContext);
        }
        if (!empty($rules)) {
            $result = true;
            foreach ($rules as $rule) {
                foreach ($rule->getConfigurations() as $config) {
                    $method = $this->shippingMethodRegistry->getShippingMethod($config->getMethod());
                    if (empty($method)) {
                        $result = false;
                    }
                }
            }
        }

        return $result;
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

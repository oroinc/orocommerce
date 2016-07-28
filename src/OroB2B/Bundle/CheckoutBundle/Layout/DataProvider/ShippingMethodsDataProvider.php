<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class ShippingMethodsDataProvider implements DataProviderInterface
{
    const NAME = 'shipping_methods_provider';

    /**
     * @var array[]
     */
    protected $data;

    /** @var ShippingMethodRegistry */
    protected $registry;

    /** @var ShippingRulesProvider */
    protected $shippingRulesProvider;

    /**
     * @param ShippingMethodRegistry $registry
     * @param ShippingRulesProvider $shippingRuleProvider
     */
    public function __construct(ShippingMethodRegistry $registry, ShippingRulesProvider $shippingRuleProvider)
    {
        $this->registry = $registry;
        $this->shippingRulesProvider = $shippingRuleProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (null === $this->data) {
            /** @var BaseCheckout $entity */
            $entity = $this->getEntity($context);
            $context = [
                'checkout' => $entity,
                'line_items' => $entity->getSourceEntity()->getLineItems(),
            ];
            $shippingContext = new ShippingContextProvider($context);
            $rules = $this->shippingRulesProvider->getApplicableShippingRules($shippingContext);
            $methods = $this->getApplicableShippingMethods($entity, $rules);
            $this->data = $methods;
        }

        return $this->data;
    }

    /**
     * @param ContextInterface $context
     * @return object|null
     */
    protected function getEntity(ContextInterface $context)
    {
        $entity = null;
        $contextData = $context->data();
        if ($contextData->has('entity')) {
            $entity = $contextData->get('entity');
        }

        if (!$entity && $contextData->has('checkout')) {
            $entity = $contextData->get('checkout');
        }

        return $entity;
    }

    /**
     * @param array $applRules
     * @return array
     */
    public function getApplicableShippingMethods($context, array $applRules)
    {
        $shippingMethods = [];
        foreach ($applRules as $priority => $rule) {
            $configurations = $rule->getConfigurations()->toArray();
            /** @var ShippingRuleConfiguration $configuration */
            foreach ($configurations as $configuration) {
                $methodName = $configuration->getMethod();
                $typeName = $configuration->getType();
                /** @var ShippingMethodInterface $method */
                $method = $this->registry
                    ->getShippingMethod($methodName);
                if (!is_int(array_search($methodName, array_column($shippingMethods, 'name')))) {
                    $shippingMethods[$methodName] = [
                        'name' => $methodName,
                        'label' => $method->getLabel(),
                        'types' => []
                    ];
                }
                $col = array_column($shippingMethods[$methodName]['types'], 'name');
                $tp = array_search($typeName, array_column($shippingMethods[$methodName]['types'], 'name'));
                if (!is_int(array_search($typeName, array_column($shippingMethods[$methodName]['types'], 'name')))) {
                    $price = $method->calculatePrice($context, $configuration);
                    $shippingMethods[$methodName]['types'][] = [
                        'name' => $typeName,
                        'label' => $method->getShippingTypeLabel($typeName),
                        'price' => $price,
                    ];
                }
            }
        }
        return $shippingMethods;
    }
}

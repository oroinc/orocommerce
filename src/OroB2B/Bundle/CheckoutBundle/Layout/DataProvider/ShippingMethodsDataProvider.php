<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\CheckoutBundle\Provider\ShippingCostCalculationProvider;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class ShippingMethodsDataProvider implements DataProviderInterface
{
    const NAME = 'shipping_methods_data_provider';

    /** @var array[] */
    protected $data;

    /** @var ShippingMethodRegistry */
    protected $registry;

    /** @var ShippingRulesProvider */
    protected $shippingRulesProvider;

    /** @var  ShippingCostCalculationProvider */
    protected $shippingCostCalculator;

    /**
     * @param ShippingCostCalculationProvider $shippingCostCalculator
     * @param ShippingRulesProvider $shippingRuleProvider
     */
    public function __construct(
        ShippingCostCalculationProvider $shippingCostCalculator,
        ShippingRulesProvider $shippingRuleProvider
    ) {
        $this->shippingCostCalculator = $shippingCostCalculator;
        $this->registry = $shippingCostCalculator->getShippingMethodRegistry();
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
    public function getData(ContextInterface $layoutContext)
    {
        if (null === $this->data) {
            /** @var BaseCheckout $entity */
            $entity = $this->getEntity($layoutContext);
            $sourceEntity = $entity->getSourceEntity();
            $context = [
                'checkout' => $entity,
                'currency' => $entity->getCurrency(),
                'line_items' => $sourceEntity->getLineItems(),
            ];
            $shippingContext = new ShippingContextProvider($context);
            $rules = $this->shippingRulesProvider->getApplicableShippingRules($shippingContext);
            $this->data = $this->getApplicableShippingMethods($shippingContext, $rules);
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
     * @param ShippingContextAwareInterface $context
     * @param array $applicableRules
     * @return array
     */
    public function getApplicableShippingMethods(ShippingContextAwareInterface $context, array $applicableRules)
    {
        $shippingMethods = [];
        foreach ($applicableRules as $priority => $rule) {
            /** @var ShippingRuleConfiguration $configuration */
            $configurations = $rule->getConfigurations();
            foreach ($configurations as $configuration) {
                $methodName = $configuration->getMethod();
                $typeName = $configuration->getType();
                $method = $this->registry->getShippingMethod($methodName);
                if (!$typeName) {
                    if (!array_key_exists($methodName, $shippingMethods)) {
                        $shippingMethods[$methodName] = [
                            'name' => $methodName,
                            'label' => $method->getLabel(),
                            'price' => $method->calculatePrice($context, $configuration),
                            'shippingRuleConfig' => $configuration->getId(),
                        ];
                    }
                } else {
                    if (!array_key_exists($methodName, $shippingMethods)) {
                        $shippingMethods[$methodName] = [
                            'name' => $methodName,
                            'label' => $method->getLabel(),
                            'types' => []
                        ];
                    }
                    if (!array_key_exists($typeName, $shippingMethods[$methodName])) {
                        $shippingMethods[$methodName]['types'][$typeName] = [
                            'name' => $typeName,
                            'label' => $method->getShippingTypeLabel($typeName),
                            'price' => $method->calculatePrice($context, $configuration),
                            'shippingRuleConfig' => $configuration->getId(),
                        ];
                    }
                }
            }
        }
        return $shippingMethods;
    }
}

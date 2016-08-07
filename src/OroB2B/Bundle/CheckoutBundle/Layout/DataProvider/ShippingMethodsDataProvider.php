<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Factory\ShippingContextProviderFactory;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class ShippingMethodsDataProvider implements DataProviderInterface
{
    const NAME = 'shipping_methods_data_provider';

    /** @var array[]|null */
    protected $data = null;

    /** @var ShippingMethodRegistry */
    protected $registry;

    /** @var ShippingRulesProvider */
    protected $shippingRulesProvider;

    /** @var  ShippingContextProviderFactory */
    protected $shippingContextProviderFactory;

    /**
     * @param ShippingMethodRegistry $registry
     * @param ShippingRulesProvider $shippingRuleProvider
     * @param ShippingContextProviderFactory $shippingContextProviderFactory
     */
    public function __construct(
        ShippingMethodRegistry $registry,
        ShippingRulesProvider $shippingRuleProvider,
        ShippingContextProviderFactory $shippingContextProviderFactory
    ) {
        $this->registry = $registry;
        $this->shippingRulesProvider = $shippingRuleProvider;
        $this->shippingContextProviderFactory = $shippingContextProviderFactory;
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
            if (!empty($entity)) {
                $shippingContext = $this->shippingContextProviderFactory->create($entity);
                $rules = $this->shippingRulesProvider->getApplicableShippingRules($shippingContext);
                $this->data = $this->getApplicableShippingMethods($shippingContext, $rules);
            }
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
     * @param ShippingRule[]|array $applicableRules
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
                if (!empty($method)) {
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
        }
        
        return $shippingMethods;
    }
}

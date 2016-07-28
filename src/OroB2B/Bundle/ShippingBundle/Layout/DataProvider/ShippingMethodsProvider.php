<?php

namespace OroB2B\Bundle\ShippingBundle\Layout\DataProvider;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfigurationInterface;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class ShippingMethodsProvider implements DataProviderInterface
{
    const NAME = 'orob2b_shipping_methods_provider';

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
            $entity = $this->getEntity($context);
            $rules = $this->shippingRulesProvider->getApplicableShippingRules($entity);
            foreach ($rules as $rule) {
                $config = $rule->getConfigurations();
                $dest = $rule->getShippingDestinations();
            }
            $methods = $this->registry->getShippingMethods();
            foreach ($methods as $name => $view) {
                $this->data[$name] = [
                    'label' => $view->getLabel(),
                    'block' => $view->getBlock(),
                    'options' => $view->getOptions([]),
                ];
            }
        }

        return $this->data;
    }

    /**
     * @param ContextInterface $context
     * @return ShippingRuleConfigurationInterface|null
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
    public function getApplicableShippingMethods(array $applRules)
    {
        $shippingMethods = [];
        foreach ($applRules as $priority => $rule) {
            $configurations = $rule->getConfigurations()->toArray();
            /** @var ShippingRuleConfiguration $configuration */
            foreach ($configurations as $configuration) {
                $methodName = $configuration->getMethod();
                $typeName = $configuration->getType();
                if (!array_search($methodName, array_column($shippingMethods, 'name'))) {
                    $shippingMethods[$methodName] = [
                        'name' => $methodName,
                        'label' => $this->formatMethodLabel($methodName),
                        'types' => []
                    ];
                    if (!array_search($typeName, array_column($shippingMethods[$methodName]['types'], 'name'))) {
                        /** @var ShippingMethodInterface $method */
                        $method = $this->shippingMethodRegistry
                            ->getShippingMethod($configuration->getMethod());
                        $price = $method->calculatePrice($this->getEntity(), $configuration);
                        $shippingMethods[$methodName]['types'][] = [
                            'name' => $typeName,
                            'label' => $this->formatTypeLabel($methodName, $typeName),
                            'price' => $price,
                        ];
                    }
                }
            }
        }
    }
}

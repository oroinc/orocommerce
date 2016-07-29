<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class ShippingMethodsDataProvider implements DataProviderInterface
{
    const NAME = 'shipping_methods_provider';

    /** @var array[] */
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
    public function getData(ContextInterface $layoutContext)
    {
        if (null === $this->data) {
            /** @var BaseCheckout $entity */
            $entity = $this->getEntity($layoutContext);
            /** @var Order $sourceEntity */
            $sourceEntity = $entity->getSourceEntity();
            $context = [
                'checkout' => $entity,
                'billingAddress' => $sourceEntity->getBillingAddress(),
                'currency' => $entity->getCurrency(),
                'line_items' => $entity->getSourceEntity()->getLineItems(),
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
     * @param ShippingRule[]|array $applicableRules
     * @return array
     */
    public function getApplicableShippingMethods(ShippingContextAwareInterface $context, array $applicableRules)
    {
        $shippingMethods = [];
        foreach ($applicableRules as $priority => $rule) {
            $configurations = $rule->getConfigurations();
            foreach ($configurations as $configuration) {
                $methodName = $configuration->getMethod();
                $typeName = $configuration->getType();
                $method = $this->registry->getShippingMethod($methodName);
                if (!array_key_exists($methodName, $shippingMethods)) {
                    $shippingMethods[$methodName] = [
                        'name' => $methodName,
                        'label' => $method->getLabel(),
                        'types' => []
                    ];
                }
                if (!array_key_exists($typeName, $shippingMethods[$methodName])) {
                    $price = $method->calculatePrice($context, $configuration);
                    $shippingMethods[$methodName]['types'][$typeName] = [
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

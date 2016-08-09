<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShippingMethodsDataProvider extends AbstractServerRenderDataProvider
{
    const NAME = 'shipping_methods_data_provider';

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
     * @param Checkout $entity
     * @return array
     */
    public function getMethods(Checkout $entity)
    {
        if (null !== $entity) {
            $context = [
                'checkout' => $entity,
                'billingAddress' => $entity->getBillingAddress(),
                'currency' => $entity->getCurrency(),
            ];
            /** @var ShoppingList $sourceEntity */
            $sourceEntity = $entity->getSourceEntity();
            if (null !== $sourceEntity) {
                $context['line_items'] = $sourceEntity->getLineItems();
            }
            $shippingContext = new ShippingContextProvider($context);
            $rules = $this->shippingRulesProvider->getApplicableShippingRules($shippingContext);
            return $this->calculateApplicableShippingMethods($shippingContext, $rules);
        } else {
            return null;
        }
    }

    /**
     * @param ShippingContextAwareInterface $context
     * @param ShippingRule[]|array $applicableRules
     * @return array
     */
    protected function calculateApplicableShippingMethods(
        ShippingContextAwareInterface $context,
        array $applicableRules
    ) {
        $shippingMethods = [];
        foreach ($applicableRules as $rule) {
            /** @var ShippingRuleConfiguration $configuration */
            $configurations = $rule->getConfigurations();
            foreach ($configurations as $configuration) {
                $methodName = $configuration->getMethod();
                $typeName = $configuration->getType();
                $method = $this->registry->getShippingMethod($methodName);
                if (null !== $method) {
                    if (!$typeName || $typeName === $methodName) {
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

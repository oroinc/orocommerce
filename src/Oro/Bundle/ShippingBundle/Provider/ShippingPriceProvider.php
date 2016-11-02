<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecoratorFactory;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Component\ExpressionLanguage\ExpressionLanguage;

class ShippingPriceProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ShippingMethodRegistry */
    protected $registry;

    /** @var LineItemDecoratorFactory */
    protected $lineItemDecoratorFactory;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ShippingMethodRegistry $registry
     * @param LineItemDecoratorFactory $lineItemDecoratorFactory
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ShippingMethodRegistry $registry,
        LineItemDecoratorFactory $lineItemDecoratorFactory
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->registry = $registry;
        $this->lineItemDecoratorFactory = $lineItemDecoratorFactory;
    }

    /**
     * @param ShippingContextInterface $context
     * @return array
     */
    public function getApplicableMethodsWithTypesData(ShippingContextInterface $context)
    {
        $result = [];

        $rules = $this->getApplicableShippingRules($context);
        foreach ($rules as $rule) {
            $methodConfigs = $rule->getMethodConfigs();
            foreach ($methodConfigs as $methodConfig) {
                $method = $this->registry->getShippingMethod($methodConfig->getMethod());
                if (!$method) {
                    continue;
                }

                if (!array_key_exists($methodConfig->getMethod(), $result)) {
                    $result[$methodConfig->getMethod()] = [
                        'identifier' => $method->getIdentifier(),
                        'isGrouped' => $method->isGrouped(),
                        'label' => $method->getLabel(),
                        'sortOrder' => $method->getSortOrder(),
                        'types' => []
                    ];
                }

                // we don't use array_merge here, because some types can have numerical identifiers
                foreach ($this->getMethodTypesConfigs($context, $methodConfig) as $typeIdentifier => $type) {
                    if (!array_key_exists($typeIdentifier, $result[$methodConfig->getMethod()]['types'])) {
                        $result[$methodConfig->getMethod()]['types'][$typeIdentifier] = $type;
                    }
                }
            }
        }

        return array_filter($result, function ($options) {
            return 0 !== count($options['types']);
        });
    }

    /**
     * @param ShippingContextInterface $context
     * @param string $methodIdentifier
     * @param string|int $typeIdentifier
     * @return Price|null
     */
    public function getPrice(ShippingContextInterface $context, $methodIdentifier, $typeIdentifier)
    {
        $method = $this->registry->getShippingMethod($methodIdentifier);
        if (!$method) {
            return null;
        }

        $type = $method->getType($typeIdentifier);
        if (!$type) {
            return null;
        }

        $rules = $this->getApplicableShippingRules($context);
        foreach ($rules as $rule) {
            foreach ($rule->getMethodConfigs() as $methodConfig) {
                if ($methodConfig->getMethod() !== $methodIdentifier) {
                    continue;
                }

                $enabledTypeConfigs = $this->getEnabledTypeConfigs($methodConfig);
                foreach ($enabledTypeConfigs as $typeConfig) {
                    if ($typeConfig->getType() === $typeIdentifier) {
                        return $type->calculatePrice($context, $methodConfig->getOptions(), $typeConfig->getOptions());
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param ShippingContextInterface $context
     * @return ShippingRule[]|array
     */
    protected function getApplicableShippingRules(ShippingContextInterface $context)
    {
        $applicableRules = [];
        if ($context) {
            $rules = $this->getSortedShippingRules($context);
            foreach ($rules as $rule) {
                if ($this->expressionApplicable($rule->getConditions(), $context)
                    && $this->destinationApplicable($rule->getDestinations(), $context)
                ) {
                    $applicableRules[] = $rule;
                    if ($rule->isStopProcessing()) {
                        break;
                    }
                }
            }
        }

        return $applicableRules;
    }

    /**
     * @param string $condition
     * @param ShippingContextInterface $context
     * @return mixed
     */
    protected function expressionApplicable($condition, ShippingContextInterface $context)
    {
        if (!$condition) {
            return true;
        }
        $language = new ExpressionLanguage();
        $language->register('count', function ($field) {
            return sprintf('count(%s)', $field);
        }, function ($arguments, $field) {
            return count($field);
        });
        $lineItems = $context->getLineItems();
        try {
            return $language->evaluate($condition, [
                'lineItems' => array_map(function (ShippingLineItemInterface $lineItem) use ($lineItems) {
                    return $this->lineItemDecoratorFactory->createOrderLineItemDecorator($lineItems, $lineItem);
                }, $lineItems),
                'billingAddress' => $context->getBillingAddress(),
                'shippingAddress' => $context->getShippingAddress(),
                'shippingOrigin' => $context->getShippingOrigin(),
                'paymentMethod' => $context->getPaymentMethod(),
                'currency' => $context->getCurrency(),
                'subtotal' => $context->getSubtotal(),
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param ShippingRuleDestination[]|\Traversable $destinations
     * @param ShippingContextInterface $context
     * @return mixed
     */
    protected function destinationApplicable(\Traversable $destinations, ShippingContextInterface $context)
    {
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $context->getShippingAddress();

        foreach ($destinations as $destination) {
            if ($destination->getCountry()->getIso2Code() === $shippingAddress->getCountry()->getIso2Code()) {
                $region = $destination->getRegion();
                if ($region && $region->getCode() !== $shippingAddress->getRegionCode()) {
                    continue;
                }
                $postalCode = $destination->getPostalCode();
                if ($postalCode
                    && !in_array($shippingAddress->getPostalCode(), $this->getPostalCodesArray($postalCode), true)
                ) {
                    continue;
                }
                return true;
            }
        }

        // Rule is applicable if it doesn't have destinations
        return count($destinations) === 0;
    }

    /**
     * @param string $postalCode
     * @return array
     */
    protected function getPostalCodesArray($postalCode)
    {
        return array_map(function ($postalCode) {
            return trim($postalCode);
        }, explode(',', $postalCode));
    }

    /**
     * @param ShippingContextInterface $context
     * @return ShippingRule[]
     */
    protected function getSortedShippingRules(ShippingContextInterface $context)
    {
        /** @var ShippingRuleRepository $repository */
        $repository = $this->doctrineHelper
            ->getEntityManagerForClass('OroShippingBundle:ShippingRule')
            ->getRepository('OroShippingBundle:ShippingRule');

        return $repository->getEnabledOrderedRulesByCurrencyAndCountry(
            $context->getCurrency(),
            $context->getShippingAddress()->getCountryIso2()
        );
    }

    /**
     * @param ShippingContextInterface $context
     * @param ShippingRuleMethodConfig $methodConfig
     * @return array
     */
    protected function getMethodTypesConfigs(ShippingContextInterface $context, ShippingRuleMethodConfig $methodConfig)
    {
        $method = $this->registry->getShippingMethod($methodConfig->getMethod());

        $types = [];
        $typeConfigs = $this->getEnabledTypeConfigs($methodConfig);
        $methodOptions = $methodConfig->getOptions();

        if ($method instanceof PricesAwareShippingMethodInterface) {
            $optionsByTypes = $this->getOptionsByTypes($methodConfig, $typeConfigs->toArray());
            $prices = $method->calculatePrices($context, $methodConfig->getOptions(), $optionsByTypes);
            foreach ($prices as $typeIdentifier => $price) {
                if ($price) {
                    $types[$typeIdentifier] = $this->createTypeData(
                        $method->getType($typeIdentifier),
                        $methodOptions,
                        array_key_exists($typeIdentifier, $optionsByTypes) ? $optionsByTypes[$typeIdentifier] : [],
                        $price
                    );
                }
            }
        } else {
            foreach ($typeConfigs as $typeConfig) {
                $type = $method->getType($typeConfig->getType());
                if ($type) {
                    $options = $typeConfig->getOptions();
                    $price = $type->calculatePrice($context, $methodConfig->getOptions(), $options);
                    if ($price) {
                        $types[$type->getIdentifier()] = $this->createTypeData($type, $methodOptions, $options, $price);
                    }
                }
            }
        }
        return $types;
    }

    /**
     * @param ShippingMethodTypeInterface $type
     * @param array $methodOptions
     * @param array $typeOptions
     * @param Price|null $price
     * @return array
     */
    protected function createTypeData(
        ShippingMethodTypeInterface $type,
        array $methodOptions,
        array $typeOptions,
        Price $price = null
    ) {
        return [
            'identifier' => $type->getIdentifier(),
            'label' => $type->getLabel(),
            'sortOrder' => $type->getSortOrder(),
            'methodOptions' => $methodOptions,
            'options' => $typeOptions,
            'price' => $price,
        ];
    }

    /**
     * @param ShippingRuleMethodConfig $methodConfig
     * @param array|ShippingRuleMethodTypeConfig[] $typeConfigs
     * @return array
     */
    protected function getOptionsByTypes(ShippingRuleMethodConfig $methodConfig, array $typeConfigs)
    {
        $optionsTypesArray = [];
        foreach ($methodConfig->getTypeConfigs() as $typeConfig) {
            $optionsTypesArray[$typeConfig->getType()] = $typeConfig->getOptions();
        }

        $typesArray = [];
        foreach ($typeConfigs as $type) {
            $typesArray[] = $type->getType();
        }

        return array_intersect_key($optionsTypesArray, array_flip($typesArray));
    }

    /**
     * @param ShippingRuleMethodConfig $methodConfig
     * @return \Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig[]|Collection
     */
    protected function getEnabledTypeConfigs($methodConfig)
    {
        return $methodConfig->getTypeConfigs()->filter(function (ShippingRuleMethodTypeConfig $typeConfig) {
            return $typeConfig->isEnabled();
        });
    }
}

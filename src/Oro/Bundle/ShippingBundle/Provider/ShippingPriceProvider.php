<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ShippingPriceProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ShippingMethodRegistry */
    protected $registry;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ShippingMethodRegistry $registry
     */
    public function __construct(DoctrineHelper $doctrineHelper, ShippingMethodRegistry $registry)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->registry = $registry;
    }

    /**
     * @param ShippingContextInterface $context
     * @return array
     */
    public function getApplicableMethodsWithTypesData(ShippingContextInterface $context)
    {
        $prices = [];

        $rules = $this->getApplicableShippingRules($context);
        if (count($rules) === 0) {
            return $prices;
        }

        foreach ($rules as $rule) {
            $methodConfigs = $rule->getMethodConfigs();
            if (count($methodConfigs) > 0) {
                foreach ($methodConfigs as $methodConfig) {
                    $method = $this->registry->getShippingMethod($methodConfig->getMethod());
                    $prices[] = $this->fillMethodData($context, $method);
                }
            }
        }

        return $prices;
    }

    /**
     * @param ShippingContextInterface $context
     * @param string $methodIdentifier
     * @param string|int $typeIdentifier
     * @return Price|null
     */
    public function getApplicableMethodTypePrice(ShippingContextInterface $context, $methodIdentifier, $typeIdentifier)
    {
        $rules = $this->getApplicableShippingRules($context);
        if (count($rules) === 0) {
            return null;
        }

        foreach ($rules as $rule) {
            $methodConfigs = $rule->getMethodConfigs();
            if (count($methodConfigs) === 0) {
                continue;
            }

            foreach ($methodConfigs as $methodConfig) {
                $method = $this->registry->getShippingMethod($methodConfig->getMethod());
                if ($method->getIdentifier() === $methodIdentifier) {
                    $methodTypePrices = [];
                    if ($method instanceof PricesAwareShippingMethodInterface) {
                        $methodTypePrices = $this->
                            getAwareShippingMethodTypePrices($context, $method, $typeIdentifier)
                        ;
                    }

                    $methodType = $method->getType($typeIdentifier);
                    if ($methodType !== null) {
                        return array_key_exists($methodType->getIdentifier(), $methodTypePrices) ?
                            $methodTypePrices[$methodType->getIdentifier()] :
                            $methodType->calculatePrice($context, $method->getOptions(), $methodType->getOptions())
                        ;
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
        $result = true;
        if ($condition) {
            $language = new ExpressionLanguage();
            try {
                $result = $language->evaluate($condition, $context->getOptions());
            } catch (\Exception $e) {
                $result = false;
            }
        }

        return $result;
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
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $context->getShippingAddress();

        /** @var ShippingRuleRepository $repository */
        $repository = $this->doctrineHelper
            ->getEntityManagerForClass('OroShippingBundle:ShippingRule')
            ->getRepository('OroShippingBundle:ShippingRule')
        ;

        return $repository->getEnabledOrderedRulesByCurrencyAndCountry(
            $context->getCurrency(),
            $shippingAddress->getCountry()
        );
    }

    /**
     * @param ShippingContextInterface $context
     * @param ShippingMethodInterface $method
     * @return array
     */
    protected function fillMethodData(ShippingContextInterface $context, ShippingMethodInterface $method)
    {
        $methodPrice = [];
        $methodPrice['identifier'] = $method->getIdentifier();
        $methodPrice['isGrouped'] = $method->isGrouped();
        $methodPrice['label'] = $method->getLabel();
        $methodPrice['sortOrder'] = $method->getSortOrder();
        $methodPrice['options'] = $method->getOptions();

        $types = [];
        $methodTypes = $method->getTypes();
        if (count($methodTypes) > 0) {
            $methodTypePrices = [];
            if ($method instanceof PricesAwareShippingMethodInterface) {
                $methodTypePrices = $this->getAwareShippingMethodTypePrices($context, $method);
            }

            foreach ($methodTypes as $methodType) {
                $types[] = [
                    'identifier' => $methodType->getIdentifier(),
                    'label' => $methodType->getLabel(),
                    'sortOrder' => $methodType->getSortOrder(),
                    'options' => $methodType->getOptions(),
                    'price' => array_key_exists($methodType->getIdentifier(), $methodTypePrices) ?
                        $methodTypePrices[$methodType->getIdentifier()] :
                        $methodType->calculatePrice($context, $method->getOptions(), $methodType->getOptions()),
                ];
            }
        }
        $methodPrice['types'] = $types;

        return $methodPrice;
    }

    /**
     * @param ShippingContextInterface $context
     * @param PricesAwareShippingMethodInterface $method
     * @param string|int|null $methodTypeIdentifier
     * @return array
     */
    protected function getAwareShippingMethodTypePrices(
        ShippingContextInterface $context,
        PricesAwareShippingMethodInterface $method,
        $methodTypeIdentifier = null
    ) {
        $optionsByTypes = [];
        if ($method instanceof ShippingMethodInterface) {
            /** @var ShippingMethodInterface $method */
            if ($methodTypeIdentifier !== null) {
                $methodType = $method->getType($methodTypeIdentifier);
                $optionsByTypes[] = [$methodType->getIdentifier() => $methodType->getOptions()];
            } else {
                $methodTypes = $method->getTypes();
                foreach ($methodTypes as $methodType) {
                    $optionsByTypes[] = [$methodType->getIdentifier() => $methodType->getOptions()];
                }
            }
        }

        return $method->calculatePrices($context, $method->getOptions(), $optionsByTypes);
    }
}

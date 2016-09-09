<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
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
                /** @var ShippingRuleMethodConfig $methodConfig */
                foreach ($methodConfigs as $methodConfig) {
                    $prices[$methodConfig->getMethod()] = $this->fillMethodData($context, $methodConfig);
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
    public function getPrice(ShippingContextInterface $context, $methodIdentifier, $typeIdentifier)
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
                    $methodType = $method->getType($typeIdentifier);
                    if ($methodType !== null) {
                        return $methodType->calculatePrice(
                            $context,
                            $methodConfig->getOptions() ? : [],
                            $methodConfig->getOptionsByType($methodType)
                        );
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
                $result = $language->evaluate($condition, [
                    'lineItems' => $context->getLineItems(),
                    'billingAddress' => $context->getBillingAddress(),
                    'shippingAddress' => $context->getShippingAddress(),
                    'shippingOrigin' => $context->getShippingOrigin(),
                    'paymentMethod' => $context->getPaymentMethod(),
                    'currency' => $context->getCurrency(),
                    'subtotal' => $context->getSubtotal(),
                ]);
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
     * @param ShippingRuleMethodConfig $methodConfig
     * @return array
     */
    protected function fillMethodData(ShippingContextInterface $context, ShippingRuleMethodConfig $methodConfig)
    {
        $method = $this->registry->getShippingMethod($methodConfig->getMethod());
        $methodConfigOptions = $methodConfig->getOptions() ? : [];

        $methodTypePrices = [];
        $methodTypes = $method->getTypes();
        if ($method instanceof PricesAwareShippingMethodInterface) {
            $methodTypePrices = $method->calculatePrices(
                $context,
                $methodConfigOptions,
                $methodConfig->getOptionsByTypes($methodTypes)
            );
        }

        $types = [];
        foreach ($methodTypes as $methodType) {
            $methodTypeOptions = $methodConfig->getOptionsByType($methodType);

            $price = array_key_exists($methodType->getIdentifier(), $methodTypePrices) ?
                $methodTypePrices[$methodType->getIdentifier()] :
                $methodType->calculatePrice(
                    $context,
                    $methodConfigOptions,
                    $methodTypeOptions
                );

            if ($price) {
                $types[$methodType->getIdentifier()] = [
                    'identifier' => $methodType->getIdentifier(),
                    'label'      => $methodType->getLabel(),
                    'sortOrder'  => $methodType->getSortOrder(),
                    'options'    => $methodTypeOptions,
                    'price'      => $price,
                ];
            }
        }

        if (count($types) === 0) {
            return null;
        }

        $methodPrice = [
            'identifier' => $method->getIdentifier(),
            'isGrouped'  => $method->isGrouped(),
            'label'      => $method->getLabel(),
            'sortOrder'  => $method->getSortOrder(),
            'options'    => $methodConfigOptions,
            'types'      => $types
        ];

        return $methodPrice;
    }
}

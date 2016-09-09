<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ShippingRulesProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ShippingContextInterface $shippingRuleContext
     * @return ShippingRule[]
     */
    public function getApplicableShippingRules(ShippingContextInterface $shippingRuleContext)
    {
        $applicableRules = [];
        if ($shippingRuleContext) {
            $rules = $this->getSortedShippingRules($shippingRuleContext);
            foreach ($rules as $rule) {
                if ($this->expressionApplicable($rule->getConditions(), $shippingRuleContext)
                    && $this->destinationApplicable($rule->getDestinations(), $shippingRuleContext)
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
     * @param ShippingContextInterface $shippingRuleContext
     * @return ShippingRule[]
     */
    protected function getSortedShippingRules(ShippingContextInterface $shippingRuleContext)
    {
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $shippingRuleContext->getShippingAddress();

        /** @var ShippingRuleRepository $repository */
        $repository = $this->doctrineHelper
            ->getEntityManagerForClass('OroShippingBundle:ShippingRule')
            ->getRepository('OroShippingBundle:ShippingRule');

        return $repository->getEnabledOrderedRulesByCurrencyAndCountry(
            $shippingRuleContext->getCurrency(),
            $shippingAddress->getCountry()
        );
    }

    /**
     * @param string $condition
     * @param ShippingContextInterface $shippingRuleContext
     * @return mixed
     */
    protected function expressionApplicable($condition, ShippingContextInterface $shippingRuleContext)
    {
        $result = true;
        if ($condition) {
            $language = new ExpressionLanguage();
            try {
                $result = $language->evaluate($condition, [
                    'lineItems' => $shippingRuleContext->getLineItems(),
                    'billingAddress' => $shippingRuleContext->getBillingAddress(),
                    'shippingAddress' => $shippingRuleContext->getShippingAddress(),
                    'shippingOrigin' => $shippingRuleContext->getShippingOrigin(),
                    'paymentMethod' => $shippingRuleContext->getPaymentMethod(),
                    'currency' => $shippingRuleContext->getCurrency(),
                    'subtotal' => $shippingRuleContext->getSubtotal(),
                ]);
            } catch (\Exception $e) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * @param ShippingRuleDestination[]|\Traversable $destinations
     * @param ShippingContextInterface $shippingRuleContext
     * @return mixed
     */
    protected function destinationApplicable(\Traversable $destinations, ShippingContextInterface $shippingRuleContext)
    {
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $shippingRuleContext->getShippingAddress();

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
     * @param $postalCode
     * @return array
     */
    protected function getPostalCodesArray($postalCode)
    {
        return array_map(function ($postalCode) {
            return trim($postalCode);
        }, explode(',', $postalCode));
    }
}

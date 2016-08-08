<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;

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
     * @param ShippingContextAwareInterface $context
     * @return ShippingRule[]
     */
    public function getApplicableShippingRules(ShippingContextAwareInterface $context)
    {
        $shippingRuleContext = $context->getShippingContext();
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
     * @param array $shippingRuleContext
     * @return ShippingRule[]
     */
    protected function getSortedShippingRules(array $shippingRuleContext)
    {
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $shippingRuleContext['shippingAddress'];

        /** @var ShippingRuleRepository $repository */
        $repository = $this->doctrineHelper
            ->getEntityManagerForClass('OroB2BShippingBundle:ShippingRule')
            ->getRepository('OroB2BShippingBundle:ShippingRule');
        return $repository->getEnabledOrderedRulesByCurrencyAndCountry(
            $shippingRuleContext['currency'],
            $shippingAddress->getCountry()
        );
    }

    /**
     * @param string $condition
     * @param array $shippingRuleContext
     * @return mixed
     */
    protected function expressionApplicable($condition, array $shippingRuleContext)
    {
        if ($condition) {
            $language = new ExpressionLanguage();
            return $language->evaluate($condition, $shippingRuleContext);
        }
        return true;
    }

    /**
     * @param ShippingRuleDestination[]|\Traversable $destinations
     * @param array $shippingRuleContext
     * @return mixed
     */
    protected function destinationApplicable(\Traversable $destinations, array $shippingRuleContext)
    {
        /** @var AbstractAddress $shippingAddress */
        $shippingAddress = $shippingRuleContext['shippingAddress'];

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

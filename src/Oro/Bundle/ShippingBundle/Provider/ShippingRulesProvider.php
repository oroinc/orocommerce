<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\LineItemDecoratorFactory;
use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Psr\Log\LoggerInterface;

class ShippingRulesProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var LineItemDecoratorFactory
     */
    protected $lineItemDecoratorFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param LineItemDecoratorFactory $lineItemDecoratorFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        LineItemDecoratorFactory $lineItemDecoratorFactory,
        LoggerInterface $logger
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->lineItemDecoratorFactory = $lineItemDecoratorFactory;
        $this->logger = $logger;
    }

    /**
     * @param ShippingContextInterface $context
     * @return ShippingRule[]
     */
    public function getApplicableShippingRules(ShippingContextInterface $context)
    {
        $applicableRules = [];
        foreach ($this->getSortedShippingRules($context) as $rule) {
            if ($this->expressionApplicable($rule, $context)
                && $this->destinationApplicable($rule->getDestinations(), $context)
            ) {
                $applicableRules[] = $rule;
                if ($rule->isStopProcessing()) {
                    break;
                }
            }
        }

        return $applicableRules;
    }

    /**
     * @param ShippingRule $rule
     * @param ShippingContextInterface $context
     * @return mixed
     */
    protected function expressionApplicable(ShippingRule $rule, ShippingContextInterface $context)
    {
        $condition = $rule->getConditions();
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
            $this->logger->error(
                'Shipping rule condition evaluation error: ' . $e->getMessage(),
                ['ShippingRule::$id' => $rule->getId()]
            );
        }
        return false;
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
}

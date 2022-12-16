<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewFactory;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides shipping methods based on configuration
 */
class ShippingPriceProvider implements ShippingPriceProviderInterface
{
    use MemoryCacheProviderAwareTrait;

    private MethodsConfigsRulesByContextProviderInterface $shippingRulesProvider;
    private ShippingMethodProviderInterface $shippingMethodProvider;
    private ShippingPriceCache $priceCache;
    private ShippingMethodViewFactory $shippingMethodViewFactory;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        MethodsConfigsRulesByContextProviderInterface $shippingRulesProvider,
        ShippingMethodProviderInterface $shippingMethodProvider,
        ShippingPriceCache $priceCache,
        ShippingMethodViewFactory $shippingMethodViewFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->shippingRulesProvider = $shippingRulesProvider;
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->priceCache = $priceCache;
        $this->shippingMethodViewFactory = $shippingMethodViewFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(ShippingContextInterface $context)
    {
        return $this->getMemoryCacheProvider()->get(
            ['shipping_context' => $context],
            function () use ($context) {
                return $this->getActualApplicableMethodsViews($context);
            }
        );
    }

    private function getActualApplicableMethodsViews(ShippingContextInterface $context): ShippingMethodViewCollection
    {
        $methodCollection = new ShippingMethodViewCollection();

        $rules = $this->shippingRulesProvider->getShippingMethodsConfigsRules($context);
        foreach ($rules as $rule) {
            foreach ($rule->getMethodConfigs() as $methodConfig) {
                $methodId = $methodConfig->getMethod();
                $method = $this->shippingMethodProvider->getShippingMethod($methodId);

                if (!$method) {
                    continue;
                }

                $methodView = $this->shippingMethodViewFactory->createMethodView(
                    $methodId,
                    $method->getLabel(),
                    $method->isGrouped(),
                    $method->getSortOrder()
                );

                $methodCollection->addMethodView($methodId, $methodView);

                $types = $this->getApplicableMethodTypesViews($context, $methodConfig);

                if (\count($types) === 0) {
                    continue;
                }

                $methodCollection->addMethodTypesViews($methodId, $types);
            }
        }

        $event = new ApplicableMethodsEvent($methodCollection, $context->getSourceEntity());
        $this->eventDispatcher->dispatch($event, ApplicableMethodsEvent::NAME);

        return $event->getMethodCollection();
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getPrice(ShippingContextInterface $context, $methodId, $typeId)
    {
        if (!$methodId) {
            return null;
        }

        $method = $this->shippingMethodProvider->getShippingMethod($methodId);
        if (!$method) {
            return null;
        }

        $type = $method->getType($typeId);
        if (!$type) {
            return null;
        }

        $rules = $this->shippingRulesProvider->getShippingMethodsConfigsRules($context);
        foreach ($rules as $rule) {
            if ($this->priceCache->hasPrice($context, $methodId, $typeId, $rule->getId())) {
                return $this->priceCache->getPrice($context, $methodId, $typeId, $rule->getId());
            }

            foreach ($rule->getMethodConfigs() as $methodConfig) {
                if ($methodConfig->getMethod() !== $methodId) {
                    continue;
                }

                $typesOptions = $this->getEnabledTypesOptions($methodConfig->getTypeConfigs()->toArray());
                if (\array_key_exists($typeId, $typesOptions)) {
                    $price = $type->calculatePrice($context, $methodConfig->getOptions(), $typesOptions[$typeId]);
                    if ($price) {
                        $this->priceCache->savePrice($context, $methodId, $typeId, $rule->getId(), $price);
                    }

                    return $price;
                }
            }
        }

        return null;
    }

    private function getApplicableMethodTypesViews(
        ShippingContextInterface $context,
        ShippingMethodConfig $methodConfig
    ): array {
        $method = $this->shippingMethodProvider->getShippingMethod($methodConfig->getMethod());
        $rule = $methodConfig->getMethodConfigsRule();
        $methodId = $method->getIdentifier();
        $methodOptions = $methodConfig->getOptions();
        $typesOptions = $this->getEnabledTypesOptions($methodConfig->getTypeConfigs()->toArray());

        $prices = [];
        foreach ($typesOptions as $typeId => $typeOptions) {
            if ($this->priceCache->hasPrice($context, $methodId, $typeId, $rule->getId())) {
                $prices[$typeId] = $this->priceCache->getPrice($context, $methodId, $typeId, $rule->getId());
            }
        }
        $requestedTypesOptions = array_diff_key($typesOptions, $prices);

        if ($method instanceof PricesAwareShippingMethodInterface && \count($requestedTypesOptions) > 0) {
            $prices = array_replace(
                $prices,
                $method->calculatePrices($context, $methodOptions, $requestedTypesOptions)
            );
        } else {
            foreach ($requestedTypesOptions as $typeId => $typeOptions) {
                $type = $method->getType($typeId);
                if ($type) {
                    $prices[$typeId] = $type->calculatePrice($context, $methodOptions, $typeOptions);
                }
            }
        }

        $types = [];
        foreach (array_filter($prices) as $typeId => $price) {
            if (\array_key_exists($typeId, $requestedTypesOptions)) {
                $this->priceCache->savePrice($context, $methodId, $typeId, $rule->getId(), $price);
            }
            $type = $method->getType($typeId);
            $types[$typeId] = $this->shippingMethodViewFactory->createMethodTypeView(
                $type->getIdentifier(),
                $type->getLabel(),
                $type->getSortOrder(),
                $price
            );
        }

        return $types;
    }

    private function getEnabledTypesOptions(array $typeConfigs): array
    {
        $result = [];
        foreach ($typeConfigs as $config) {
            if ($config->isEnabled()) {
                $result[$config->getType()] = $config->getOptions();
            }
        }

        return $result;
    }
}

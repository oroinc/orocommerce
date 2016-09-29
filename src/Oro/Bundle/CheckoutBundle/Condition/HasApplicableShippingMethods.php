<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check applicable shipping methods
 * Usage:
 * @has_applicable_shipping_methods:
 *      entity: ~
 */
class HasApplicableShippingMethods extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'has_applicable_shipping_methods';

    /** @var ShippingMethodRegistry */
    protected $shippingMethodRegistry;

    /** ShippingPriceProvider */
    protected $shippingPriceProvider;

    /** ShippingContextProviderFactory */
    protected $shippingContextProviderFactory;

    /** @var Checkout */
    protected $entity;

    /**
     * @param ShippingMethodRegistry $shippingMethodRegistry
     * @param ShippingPriceProvider $shippingPriceProvider
     * @param ShippingContextProviderFactory $shippingContextProviderFactory
     */
    public function __construct(
        ShippingMethodRegistry $shippingMethodRegistry,
        ShippingPriceProvider $shippingPriceProvider,
        ShippingContextProviderFactory $shippingContextProviderFactory
    ) {
        $this->shippingMethodRegistry = $shippingMethodRegistry;
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->shippingContextProviderFactory = $shippingContextProviderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('entity', $options)) {
            $this->entity = $options['entity'];
        } elseif (array_key_exists(0, $options)) {
            $this->entity = $options[0];
        }

        if (!$this->entity) {
            throw new InvalidArgumentException('Missing "entity" option');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        /** @var Checkout $entity */
        $entity = $this->resolveValue($context, $this->entity, false);

        $methodsData = [];
        if (null !== $entity) {
            $shippingContext = $this->shippingContextProviderFactory->create($entity);
            $methodsData = $this->shippingPriceProvider->getApplicableMethodsWithTypesData($shippingContext);
        }

        return count($methodsData) !== 0;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->entity]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->entity], $factoryAccessor);
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check shipping method supports
 * Usage:
 * @shipping_method_supports:
 *      entity: $checkout
 */
class ShippingMethodSupports extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'shipping_method_supports';

    /**
     * @var Checkout
     */
    protected $entity;

    /**
     * @var ShippingPriceProvider
     */
    protected $shippingPriceProvider;

    /**
     * @var ShippingContextProviderFactory
     */
    protected $shippingContextProviderFactory;

    /**
     * @param ShippingPriceProvider $shippingPriceProvider
     * @param ShippingContextProviderFactory $shippingContextProviderFactory
     */
    public function __construct(
        ShippingPriceProvider $shippingPriceProvider,
        ShippingContextProviderFactory $shippingContextProviderFactory
    ) {
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->shippingContextProviderFactory = $shippingContextProviderFactory;
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
    public function initialize(array $options)
    {
        if (array_key_exists('entity', $options)) {
            $this->entity = $options['entity'];
        }

        if (!$this->entity) {
            throw new InvalidArgumentException('Missing "entity" option');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        /** @var Checkout $entity */
        $entity = $this->resolveValue($context, $this->entity, false);

        $shippingContext = $this->shippingContextProviderFactory->create($entity);
        $allMethodsData = $this->shippingPriceProvider->getApplicableMethodsWithTypesData($shippingContext);

        foreach ($allMethodsData as $method) {
            if (!array_key_exists('identifier', $method) || !array_key_exists('types', $method)) {
                continue;
            }
            if ($method['identifier'] === $entity->getShippingMethod()) {
                foreach ($method['types'] as $type) {
                    if (array_key_exists('identifier', $type)
                        && $type['identifier'] === $entity->getShippingMethodType()) {
                        return true;
                    }
                }
                break;
            }
        }

        return false;
    }

    /**
     * Gets an array representation of the expression.
     *
     * @return array
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

<?php

namespace Oro\Bundle\ShippingBundle\Condition;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Check applicable shipping methods for a specific shipping context.
 * Usage:
 * @has_applicable_shipping_methods:
 *      shippingContext: ~
 */
class HasApplicableShippingMethods extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private ShippingPriceProviderInterface $shippingPriceProvider;
    private mixed $shippingContext = null;

    public function __construct(ShippingPriceProviderInterface $shippingPriceProvider)
    {
        $this->shippingPriceProvider = $shippingPriceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (\array_key_exists('shippingContext', $options)) {
            $this->shippingContext = $options['shippingContext'];
        } elseif (\array_key_exists(0, $options)) {
            $this->shippingContext = $options[0];
        }

        if (!$this->shippingContext) {
            throw new InvalidArgumentException('Missing "shippingContext" option');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'has_applicable_shipping_methods';
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        /** @var ShippingContextInterface $shippingContext */
        $shippingContext = $this->resolveValue($context, $this->shippingContext, false);
        if (null === $shippingContext) {
            return false;
        }

        return !$this->shippingPriceProvider->getApplicableMethodsViews($shippingContext)->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->shippingContext]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->shippingContext], $factoryAccessor);
    }
}

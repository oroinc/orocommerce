<?php

namespace Oro\Bundle\ShippingBundle\Condition;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;
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

    /** @var ShippingMethodProviderInterface */
    protected $shippingMethodProvider;

    /** ShippingPriceProvider */
    protected $shippingPriceProvider;

    /** @var mixed */
    protected $shippingContext;

    public function __construct(
        ShippingMethodProviderInterface $shippingMethodProvider,
        ShippingPriceProviderInterface $shippingPriceProvider
    ) {
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->shippingPriceProvider = $shippingPriceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (array_key_exists('shippingContext', $options)) {
            $this->shippingContext = $options['shippingContext'];
        } elseif (array_key_exists(0, $options)) {
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
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        /** @var ShippingContextInterface $shippingContext */
        $shippingContext = $this->resolveValue($context, $this->shippingContext, false);

        $methodsData = [];
        if (null !== $shippingContext) {
            $methodsData = $this->shippingPriceProvider->getApplicableMethodsViews($shippingContext);
        }

        return count($methodsData) !== 0;
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

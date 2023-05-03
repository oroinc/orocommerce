<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * Condition to check if line items shipping methods should be updated from stored in checkout attribute value.
 */
class IsLineItemsShippingMethodsUpdateRequired extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private const LINE_ITEMS = 'line_items';
    private const LINE_ITEMS_SHIPPING_DATA = 'line_items_shipping_data';
    private const CONDITION_NAME = 'is_line_items_shipping_methods_update_required';

    /** @var mixed */
    private $lineItems;

    /** @var mixed */
    private $lineItemsShippingData = null;

    /**
     * Line items shipping methods should be updated if stored checkout value is not empty but some line items has
     * empty shipping method.
     *
     * @param mixed $context
     * @return bool
     */
    protected function isConditionAllowed($context)
    {
        $lineItemsShippingData = $this->resolveValue($context, $this->lineItemsShippingData);

        if (empty($lineItemsShippingData)) {
            return false;
        }

        $lineItems = $this->resolveValue($context, $this->lineItems);

        /** @var CheckoutLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            if (!$lineItem->hasShippingMethodData()) {
                return true;
            }
        }

        return false;
    }

    public function getName()
    {
        return self::CONDITION_NAME;
    }

    public function initialize(array $options)
    {
        if (array_key_exists(self::LINE_ITEMS, $options)) {
            $this->lineItems = $options[self::LINE_ITEMS];
        }

        if (array_key_exists(0, $options)) {
            $this->lineItems = $options[0];
        }

        if (!$this->lineItems) {
            throw new InvalidArgumentException(sprintf('Missing "%s" option', self::LINE_ITEMS));
        }

        if (array_key_exists(self::LINE_ITEMS_SHIPPING_DATA, $options)) {
            $this->lineItemsShippingData = $options[self::LINE_ITEMS_SHIPPING_DATA];
        }

        if (array_key_exists(1, $options)) {
            $this->lineItemsShippingData = $options[1];
        }

        if (null === $this->lineItemsShippingData) {
            throw new InvalidArgumentException(sprintf('Missing "%s" option', self::LINE_ITEMS_SHIPPING_DATA));
        }

        return $this;
    }

    public function toArray()
    {
        return $this->convertToArray([$this->lineItems, $this->lineItemsShippingData]);
    }

    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->lineItems, $this->lineItemsShippingData], $factoryAccessor);
    }
}

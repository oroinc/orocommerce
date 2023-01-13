<?php

namespace Oro\Bundle\CheckoutBundle\Action\MultiShipping;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\GroupedCheckoutLineItemsProvider;
use Oro\Bundle\CheckoutBundle\Splitter\MultiShipping\CheckoutSplitter;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Split checkout according to grouped line items.
 */
class SplitCheckoutAction extends AbstractAction
{
    private CheckoutSplitter $checkoutSplitter;
    private GroupedCheckoutLineItemsProvider $groupedLineItemsProvider;
    protected PropertyPath $attribute;
    protected PropertyPath $checkout;
    protected PropertyPath $groupedLineItemsIds;

    public function __construct(
        ContextAccessor $contextAccessor,
        CheckoutSplitter $checkoutSplitter,
        GroupedCheckoutLineItemsProvider $groupedLineItemsProvider
    ) {
        parent::__construct($contextAccessor);
        $this->checkoutSplitter = $checkoutSplitter;
        $this->groupedLineItemsProvider = $groupedLineItemsProvider;
    }

    protected function executeAction($context)
    {
        $checkout = $this->contextAccessor->getValue($context, $this->checkout);
        $groupedLineItemIds = $this->contextAccessor->getValue($context, $this->groupedLineItemsIds);

        $splitItems = $this->groupedLineItemsProvider->getGroupedLineItemsByIds($checkout, $groupedLineItemIds);
        $splitCheckouts = $this->checkoutSplitter->split($checkout, $splitItems);

        $this->contextAccessor->setValue($context, $this->attribute, $splitCheckouts);
    }

    public function initialize(array $options)
    {
        if (!array_key_exists('attribute', $options)) {
            throw new InvalidParameterException('"attribute" parameter is required');
        }

        $this->attribute = $options['attribute'];

        if (!array_key_exists('checkout', $options)) {
            throw new InvalidParameterException('"checkout" parameter is required');
        }

        $this->checkout = $options['checkout'];

        if (!array_key_exists('groupedLineItems', $options)) {
            throw new InvalidParameterException('"groupedLineItems" parameter is required');
        }

        $this->groupedLineItemsIds = $options['groupedLineItems'];

        return $this;
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\Model\Action;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;

/**
 * Gets OrderLineItem entities from Checkout and sets to the specified attribute.
 *
 * Usage:
 *  - '@get_order_line_items':
 *    attribute: $.orderLineItems
 *    checkout: $.checkout
 *    disable_price_filter: true
 *    config_visibility_path: $.visibilityPath
 */
class GetOrderLineItems extends AbstractAction
{
    protected array $options;

    private CheckoutLineItemsManager $checkoutLineItemsManager;

    public function __construct(
        ContextAccessor $contextAccessor,
        CheckoutLineItemsManager $checkoutLineItemsManager
    ) {
        parent::__construct($contextAccessor);

        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
    }

    #[\Override]
    public function initialize(array $options)
    {
        if (empty($options['checkout'])) {
            throw new InvalidParameterException('Checkout name parameter is required');
        }

        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }

        if (
            !array_key_exists('disable_price_filter', $options)
            && array_key_exists('config_visibility_path', $options)
        ) {
            throw new InvalidParameterException(
                'Attribute disable_price_filter is required if config_visibility_path is specified'
            );
        }

        $this->options = $options;

        return $this;
    }

    #[\Override]
    protected function executeAction($context)
    {
        /** @var Checkout $checkout */
        $checkout = $this->contextAccessor->getValue($context, $this->options['checkout']);
        $arguments = [$checkout];

        $disablePriceFilter = $this->getOption($this->options, 'disable_price_filter', null);
        if ($disablePriceFilter !== null) {
            $arguments[] =  $this->contextAccessor->getValue($context, $disablePriceFilter);
        }

        $configVisibilityPath = $this->getOption($this->options, 'config_visibility_path', null);
        if ($configVisibilityPath !== null) {
            $arguments[] =  $this->contextAccessor->getValue($context, $configVisibilityPath);
        }

        $lineItems = $this->checkoutLineItemsManager->getData(...$arguments);

        $this->contextAccessor->setValue($context, $this->options['attribute'], $lineItems);
    }
}

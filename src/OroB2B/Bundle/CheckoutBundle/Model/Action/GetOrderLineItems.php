<?php

namespace Oro\Bundle\CheckoutBundle\Model\Action;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;

class GetOrderLineItems extends AbstractAction
{
    const OPTION_KEY_CHECKOUT = 'checkout';
    const OPTION_KEY_ATTRIBUTE = 'attribute';

    protected $lineItemProvider;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @param ContextAccessor $contextAccessor
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        CheckoutLineItemsManager $checkoutLineItemsManager
    ) {
        parent::__construct($contextAccessor);

        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options[self::OPTION_KEY_CHECKOUT])) {
            throw new InvalidParameterException('Checkout name parameter is required');
        }

        if (empty($options[self::OPTION_KEY_ATTRIBUTE])) {
            throw new InvalidParameterException('Attribute name parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        /** @var Checkout $checkout */
        $checkout = $this->contextAccessor->getValue($context, $this->options['checkout']);
        $lineItems = $this->checkoutLineItemsManager->getData($checkout);

        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_ATTRIBUTE], $lineItems);
    }
}

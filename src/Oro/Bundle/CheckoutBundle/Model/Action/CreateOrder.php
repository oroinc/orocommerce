<?php

namespace Oro\Bundle\CheckoutBundle\Model\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Create Order action (workflow, operation)
 *
 * Usage:
 * - '@create_order':
 *   attribute: $.order
 *   checkout: $.checkout
 *   data:
 *     billingAddress: $.billingAddress
 *     shippingAddress: $.shippingAddress
 *     sourceEntityClass: $.sourceDocumentEntityClassName
 *     paymentTerm: $.paymentTerm
 *     lineItems: $.lineItems
 */
class CreateOrder extends AbstractAction
{
    const OPTION_KEY_ATTRIBUTE = 'attribute';
    const OPTION_KEY_CHECKOUT = 'checkout';
    const OPTION_KEY_DATA = 'data';

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var MapperInterface
     */
    protected $mapper;

    /**
     * @var EntityPaymentMethodsProvider
     */
    protected $paymentMethodsProvider;

    public function __construct(
        ContextAccessor $contextAccessor,
        MapperInterface $mapper,
        EntityPaymentMethodsProvider $paymentMethodsProvider
    ) {
        parent::__construct($contextAccessor);

        $this->mapper = $mapper;
        $this->paymentMethodsProvider = $paymentMethodsProvider;
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

        if (!$options[self::OPTION_KEY_ATTRIBUTE] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Attribute must be valid property definition');
        }

        if (!empty($options[self::OPTION_KEY_DATA]) && !is_array($options[self::OPTION_KEY_DATA])) {
            throw new InvalidParameterException('Object data must be an array');
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
        $checkout = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_CHECKOUT]);

        $additionalData = [];
        if (!empty($this->options[self::OPTION_KEY_DATA])) {
            $additionalData = $this->resolveArguments(
                $context,
                $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_DATA])
            );
        }

        $order = $this->mapper->map($checkout, $additionalData);
        $this->paymentMethodsProvider->storePaymentMethodsToEntity($order, [$checkout->getPaymentMethod()]);

        $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_ATTRIBUTE], $order);
    }

    /**
     * @param mixed $context
     * @param array $arguments
     * @return array
     */
    protected function resolveArguments($context, array $arguments = [])
    {
        foreach ($arguments as &$argument) {
            if (is_array($argument)) {
                $argument = $this->resolveArguments($context, $argument);
            } else {
                $argument = $this->contextAccessor->getValue($context, $argument);
            }
        }

        return $arguments;
    }
}

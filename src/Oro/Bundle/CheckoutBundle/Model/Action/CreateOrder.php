<?php

namespace Oro\Bundle\CheckoutBundle\Model\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\Action\Exception\InvalidParameterException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

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
     * @param ContextAccessor $contextAccessor
     * @param MapperInterface $mapper
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        MapperInterface $mapper
    ) {
        parent::__construct($contextAccessor);

        $this->mapper = $mapper;
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

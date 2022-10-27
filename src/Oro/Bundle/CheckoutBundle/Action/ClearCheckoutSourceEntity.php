<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Event\CheckoutSourceEntityClearEvent;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * Usage:
 * @clear_checkout_source_entity: $path.to.checkout
 */
class ClearCheckoutSourceEntity extends AbstractAction
{
    public const NAME = 'clear_checkout_source_entity';

    /**
     * @var mixed
     */
    protected $target;

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $value = $this->contextAccessor->getValue($context, $this->target);
        if (!is_object($value)) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects reference to entity as parameter, %s is given.',
                    static::NAME,
                    gettype($value)
                )
            );
        }

        if (!$value instanceof CheckoutInterface) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects entity instanceof "%s", "%s" is given.',
                    static::NAME,
                    CheckoutInterface::class,
                    get_class($value)
                )
            );
        }

        $checkoutSourceEntity = $value->getSourceEntity();
        if (null === $checkoutSourceEntity) {
            return;
        }

        if (!$checkoutSourceEntity instanceof ProductLineItemsHolderInterface) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects checkout source entity instanceof "%s", "%s" is given.',
                    static::NAME,
                    ProductLineItemsHolderInterface::class,
                    get_class($value)
                )
            );
        }

        $this->eventDispatcher->dispatch(
            new CheckoutSourceEntityClearEvent($checkoutSourceEntity),
            CheckoutSourceEntityClearEvent::NAME
        );

        $checkoutSourceEntity->getLineItems()->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (1 == count($options)) {
            $this->target = reset($options);
        } else {
            throw new InvalidParameterException(
                sprintf(
                    'Parameters of "%s" action must have 1 element, but %d given',
                    static::NAME,
                    count($options)
                )
            );
        }

        return $this;
    }
}

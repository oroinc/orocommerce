<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Event\CheckoutSourceEntityRemoveEvent;
use Oro\Component\Action\Action\RemoveEntity;
use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * Remove checkout source entity from the checkout if it exists and
 * fire message about removing before and after remove. The main idea
 * of this service is to aware about automatic removing checkout source entity
 * that have place because customer selected this action within checkout process.
 *
 * Usage:
 * @remove_checkout_source_entity: $path.to.checkout
 */
class RemoveCheckoutSourceEntity extends RemoveEntity
{
    public const NAME = 'remove_checkout_source_entity';

    protected function executeAction($context)
    {
        /** @var $value CheckoutInterface */
        $value = $this->contextAccessor->getValue($context, $this->target);
        if (!is_object($value)) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects entity instanceof "%s", "%s" is given.',
                    static::NAME,
                    CheckoutInterface::class,
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

        $this->eventDispatcher->dispatch(
            new CheckoutSourceEntityRemoveEvent($checkoutSourceEntity),
            CheckoutSourceEntityRemoveEvent::BEFORE_REMOVE
        );

        $value->getSource()->clear();
        $this->getEntityManager(\get_class($checkoutSourceEntity))->remove($checkoutSourceEntity);

        $this->eventDispatcher->dispatch(
            new CheckoutSourceEntityRemoveEvent($checkoutSourceEntity),
            CheckoutSourceEntityRemoveEvent::AFTER_REMOVE
        );
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Removes empty optional kit item line items from a collection.
 */
class OrderProductKitItemLineItemCollectionRemovingListener implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::SUBMIT => 'onSubmit',
        ];
    }

    /**
     * Removes kit item line items from a collection:
     *  - if it is not represented in a form;
     *  - if it does not have a kit item;
     *  - if it does not have a product and is optional.
     */
    public function onSubmit(FormEvent $event): void
    {
        /** @var Collection<OrderProductKitItemLineItem>|null $collection */
        $collection = $event->getData();
        if (null === $collection) {
            $collection = [];
        }

        $form = $event->getForm();

        foreach ($collection as $key => $kitItemLineItem) {
            // Removes kit item line items that are not represented in a form.
            if (!$form->has((string)$key)
                // Removes kit item line item that does not have a kit item specified.
                || $kitItemLineItem->getKitItemId() === null
                || ($kitItemLineItem->isOptional() === true
                    // Removes non-optional kit item line item that does not have a chosen product.
                    && $kitItemLineItem->getProductId() === null)) {
                unset($collection[$key]);
            }
        }

        $event->setData($collection);
    }
}

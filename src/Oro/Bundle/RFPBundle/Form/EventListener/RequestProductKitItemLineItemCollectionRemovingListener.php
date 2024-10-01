<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Removes empty optional kit item line items from a collection.
 */
class RequestProductKitItemLineItemCollectionRemovingListener implements EventSubscriberInterface
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
        /** @var Collection<RequestProductKitItemLineItem>|null $collection */
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
                    && $kitItemLineItem->getProductId() === null)
                // Removes mandatory and optional kit item line items that does not have a chosen product
                // and does not have a kit item specified
                || ($kitItemLineItem->getKitItem() === null
                    && $kitItemLineItem->getProduct() === null)
            ) {
                unset($collection[$key]);
            }
        }

        $event->setData($collection);
    }
}

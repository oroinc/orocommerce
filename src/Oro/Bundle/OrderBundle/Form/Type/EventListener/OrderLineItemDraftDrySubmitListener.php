<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Handles "dry submit" form trigger for order line item draft form.
 * Clears data of fields that should be cleaned when a specific field triggers "dry submit".
 */
class OrderLineItemDraftDrySubmitListener implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT => 'handleDrySubmitTriggerOnPreSubmit',
        ];
    }

    public function handleDrySubmitTriggerOnPreSubmit(FormEvent $event): void
    {
        $data = $event->getData();
        $drySubmitTrigger = $data['drySubmitTrigger'] ?? null;
        if (!$drySubmitTrigger) {
            return;
        }

        $drySubmitTriggerPropertyPath = new PropertyPath($drySubmitTrigger);

        // Handle kit item line items trigger
        if ($this->isKitItemLineItemTrigger($drySubmitTriggerPropertyPath)) {
            $this->handleKitItemLineItemTrigger($data, $drySubmitTriggerPropertyPath);
            $event->setData($data);

            return;
        }

        // Handle regular field triggers
        $this->handleRegularFieldTrigger($data, $drySubmitTriggerPropertyPath);
        $event->setData($data);
    }

    /**
     * Checks if the trigger is from a kit item line item field.
     */
    private function isKitItemLineItemTrigger(PropertyPath $propertyPath): bool
    {
        return $propertyPath->getLength() >= 3
            && $propertyPath->getElement(0) === 'kitItemLineItems'
            && in_array($propertyPath->getElement(2), ['product', 'quantity', 'price'], true);
    }

    /**
     * Handles dry submit trigger from kit item line item fields.
     */
    private function handleKitItemLineItemTrigger(
        array &$data,
        PropertyPath $drySubmitTrigger
    ): void {
        $kitItemIndex = $drySubmitTrigger->getElement(1);

        if (empty($data['price']['is_price_changed'])) {
            // Reset price only if the price is not specified manually.
            unset($data['price']['value']);
        }

        if ($drySubmitTrigger->getElement(2) === 'product') {
            unset(
                $data['kitItemLineItems'][$kitItemIndex]['quantity'],
                $data['kitItemLineItems'][$kitItemIndex]['price']
            );
        }
    }

    /**
     * Handles dry submit trigger from regular form fields.
     */
    private function handleRegularFieldTrigger(
        array &$data,
        PropertyPathInterface $drySubmitTrigger
    ): void {
        switch ($drySubmitTrigger->getElement(0)) {
            case 'product':
            case 'isFreeForm':
                if ($data['isFreeForm'] ?? false) {
                    unset(
                        $data['kitItemLineItems']
                    );
                } else {
                    unset(
                        $data['productSku'],
                        $data['freeFormProduct'],
                        $data['productUnit'],
                        $data['quantity'],
                        $data['price']['value'],
                        $data['price']['is_price_changed'],
                        $data['kitItemLineItems']
                    );
                }

                break;
        }
    }
}

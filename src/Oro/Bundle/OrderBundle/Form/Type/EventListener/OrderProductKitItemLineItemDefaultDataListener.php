<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\ProductKit\Factory\OrderProductKitItemLineItemFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Sets default data for kit item line item form.
 */
class OrderProductKitItemLineItemDefaultDataListener implements EventSubscriberInterface
{
    private OrderProductKitItemLineItemFactory $kitItemLineItemFactory;

    public function __construct(OrderProductKitItemLineItemFactory $kitItemLineItemFactory)
    {
        $this->kitItemLineItemFactory = $kitItemLineItemFactory;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        ];
    }

    public function onPreSetData(FormEvent $event): void
    {
        if ($event->getData() !== null) {
            return;
        }

        $form = $event->getForm();
        $formConfig = $form->getConfig();
        $kitItem = $formConfig->getOption('product_kit_item');
        $isRequired = $formConfig->getOption('required');
        if ($kitItem !== null) {
            $kitItemLineItem = $this->kitItemLineItemFactory->createKitItemLineItem($kitItem);
            if ($isRequired !== true) {
                // Overrides optional flag with required option to avoid appearing of required kit items
                // in already existing order line item.
                $kitItemLineItem->setOptional(true);
                $kitItemLineItem->setProduct(null);
            }

            $event->setData($kitItemLineItem);
        }
    }
}

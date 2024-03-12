<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\EventListener;

use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\ProductKit\Factory\RequestProductKitItemLineItemFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Sets default data for kit item line item form.
 */
class RequestProductKitItemLineItemDefaultDataListener implements EventSubscriberInterface
{
    private RequestProductKitItemLineItemFactory $kitItemLineItemFactory;

    public function __construct(RequestProductKitItemLineItemFactory $kitItemLineItemFactory)
    {
        $this->kitItemLineItemFactory = $kitItemLineItemFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        ];
    }

    public function onPreSetData(FormEvent $event): void
    {
        if ($event->getData() !== null) {
            /* @var RequestProductKitItemLineItem $kitItemLineItem */
            $kitItemLineItem = $event->getData();
            $productUnit = $kitItemLineItem->getProductUnit();
            $actualKitItemProductUnit = $kitItemLineItem->getKitItem()?->getProductUnit();
            if ($actualKitItemProductUnit && $productUnit?->getCode() !== $actualKitItemProductUnit?->getCode()) {
                $kitItemLineItem->setProductUnit($actualKitItemProductUnit);
            }

            return;
        }

        $form = $event->getForm();
        $formConfig = $form->getConfig();
        $kitItem = $formConfig->getOption('product_kit_item');
        if ($kitItem !== null) {
            $kitItemLineItem = $this->kitItemLineItemFactory->createKitItemLineItem($kitItem);

            $event->setData($kitItemLineItem);
        }
    }
}

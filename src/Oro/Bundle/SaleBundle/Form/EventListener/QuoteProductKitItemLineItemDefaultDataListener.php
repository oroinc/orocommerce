<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Form\EventListener;

use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\ProductKit\Factory\QuoteProductKitItemLineItemFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Sets default data for kit item line item form.
 */
class QuoteProductKitItemLineItemDefaultDataListener implements EventSubscriberInterface
{
    private QuoteProductKitItemLineItemFactory $kitItemLineItemFactory;

    public function __construct(QuoteProductKitItemLineItemFactory $kitItemLineItemFactory)
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
            /* @var QuoteProductKitItemLineItem $kitItemLineItem */
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

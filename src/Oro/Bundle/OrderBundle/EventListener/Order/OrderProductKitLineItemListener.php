<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Twig\Environment;

/**
 * Adds the rendered kitItemLineItems form collection to the OrderEvent data for each order product kit line item.
 * Also, adds a disabled flag for the price field of the kit line item.
 */
class OrderProductKitLineItemListener
{
    private Environment $twig;

    private string $kitItemLineItemsTemplate = '@OroOrder/Form/kitItemLineItems.html.twig';

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function setKitItemLineItemsTemplate(string $kitItemLineItemsTemplate): void
    {
        $this->kitItemLineItemsTemplate = $kitItemLineItemsTemplate;
    }

    public function onOrderEvent(OrderEvent $event): void
    {
        $kitItemLineItems = $checksum = $disabledKitPrices = [];
        $lineItemsForm = $event->getForm()->has('lineItems')
            ? $event->getForm()->get('lineItems')->all()
            : [];
        foreach ($lineItemsForm as $lineItemForm) {
            /** @var OrderLineItem|null $orderLineItem */
            $orderLineItem = $lineItemForm->getData();
            if ($orderLineItem === null || $orderLineItem->getProduct()?->isKit() !== true) {
                continue;
            }

            $formView = $lineItemForm->createView();
            $fullName = $formView->vars['full_name'];
            $kitItemLineItems[$fullName] = $this->twig->render(
                $this->kitItemLineItemsTemplate,
                ['form' => $formView['kitItemLineItems']]
            );
            $checksum[$fullName] = $orderLineItem->getChecksum();

            if ($orderLineItem?->getProduct()?->isKit()) {
                $disabledKitPrices[$fullName] = true;
            }
        }

        $event->getData()->offsetSet('checksum', $checksum);
        $event->getData()->offsetSet('kitItemLineItems', $kitItemLineItems);
        $event->getData()->offsetSet('disabledKitPrices', $disabledKitPrices);
    }
}

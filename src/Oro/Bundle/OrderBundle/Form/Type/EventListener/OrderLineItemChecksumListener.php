<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Updates checksum for an order line item after a form is submitted.
 */
class OrderLineItemChecksumListener implements EventSubscriberInterface
{
    private LineItemChecksumGeneratorInterface $lineItemChecksumGenerator;

    public function __construct(LineItemChecksumGeneratorInterface $lineItemChecksumGenerator)
    {
        $this->lineItemChecksumGenerator = $lineItemChecksumGenerator;
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSubmit(FormEvent $event): void
    {
        /** @var OrderLineItem|null $orderLineItem */
        $orderLineItem = $event->getData();
        if ($orderLineItem === null) {
            return;
        }

        $checksum = $this->lineItemChecksumGenerator->getChecksum($orderLineItem);
        $orderLineItem->setChecksum($checksum ?? '');
    }
}

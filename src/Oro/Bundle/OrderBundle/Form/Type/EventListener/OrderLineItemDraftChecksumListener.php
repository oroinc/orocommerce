<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Updates checksum for an order line item draft after a form is submitted.
 */
class OrderLineItemDraftChecksumListener implements EventSubscriberInterface
{
    public function __construct(private readonly LineItemChecksumGeneratorInterface $lineItemChecksumGenerator)
    {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => [
                'onPostSubmit',
                // Priority should be high enough to make this listener be executed before the validation listener -
                // {@link \Symfony\Component\Form\Extension\Validator\EventListener\ValidationListener::validateForm}
                // because the checksum must be updated before the validation starts.
                255
            ],
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

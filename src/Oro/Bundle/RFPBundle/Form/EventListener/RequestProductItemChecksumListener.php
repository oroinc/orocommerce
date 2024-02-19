<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\EventListener;

use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Updates checksum for a {@see RequestProductItem} collection after a {@see RequestProduct} form is submitted.
 */
class RequestProductItemChecksumListener implements EventSubscriberInterface
{
    private LineItemChecksumGeneratorInterface $lineItemChecksumGenerator;

    public function __construct(LineItemChecksumGeneratorInterface $lineItemChecksumGenerator)
    {
        $this->lineItemChecksumGenerator = $lineItemChecksumGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SUBMIT => 'onPostSubmit',
        ];
    }

    public function onPostSubmit(FormEvent $event): void
    {
        /** @var RequestProduct $requestProduct */
        $requestProduct = $event->getData();
        if ($requestProduct === null) {
            return;
        }

        foreach ($requestProduct->getRequestProductItems() as $requestProductItem) {
            $checksum = $this->lineItemChecksumGenerator->getChecksum($requestProductItem);
            $requestProductItem->setChecksum($checksum ?? '');
        }
    }
}

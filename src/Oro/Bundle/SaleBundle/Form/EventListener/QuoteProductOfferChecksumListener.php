<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\Form\EventListener;

use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Updates checksum for a {@see QuoteProductOffer} collection after a {@see QuoteProduct} form is submitted.
 */
class QuoteProductOfferChecksumListener implements EventSubscriberInterface
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
        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $event->getData();
        if ($quoteProduct === null) {
            return;
        }

        foreach ($quoteProduct->getQuoteProductOffers() as $quoteProductOffer) {
            $checksum = $this->lineItemChecksumGenerator->getChecksum($quoteProductOffer);
            $quoteProductOffer->setChecksum($checksum ?? '');
        }
    }
}

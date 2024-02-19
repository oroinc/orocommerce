<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;

/**
 * Adds "checksum" element with quote product offers checksums to the quote entry point data.
 */
class QuoteProductOfferChecksumQuoteEventListener
{
    public function onQuoteEvent(QuoteEvent $event): void
    {
        $checksum = [];
        $quoteProductsForm = $event->getForm()->get('quoteProducts')->all();
        foreach ($quoteProductsForm as $quoteProductForm) {
            /** @var QuoteProduct|null $quoteProduct */
            $quoteProduct = $quoteProductForm->getData();
            if ($quoteProduct === null) {
                continue;
            }

            foreach ($quoteProductForm->get('quoteProductOffers')->all() as $quoteProductOfferForm) {
                $quoteProductOffer = $quoteProductOfferForm->getData();
                if ($quoteProductOffer === null) {
                    continue;
                }

                $quoteProductOfferFormView = $quoteProductOfferForm->createView();
                $fullName = $quoteProductOfferFormView->vars['full_name'];

                $checksum[$fullName] = $quoteProductOffer->getChecksum();
            }
        }

        $event->getData()->offsetSet('checksum', $checksum);
    }
}

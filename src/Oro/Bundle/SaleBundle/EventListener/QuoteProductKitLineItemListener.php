<?php

declare(strict_types=1);

namespace Oro\Bundle\SaleBundle\EventListener;

use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Twig\Environment;

/**
 * Adds the rendered kitItemLineItems form collection to the {@see QuoteEvent} data
 * for each quote product kit line item.
 * Also, adds a disabled flag for the price field.
 */
class QuoteProductKitLineItemListener
{
    private Environment $twig;

    private string $kitItemLineItemsTemplate = '@OroSale/Form/kitItemLineItems.html.twig';

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function setKitItemLineItemsTemplate(string $kitItemLineItemsTemplate): void
    {
        $this->kitItemLineItemsTemplate = $kitItemLineItemsTemplate;
    }

    public function onQuoteEvent(QuoteEvent $event): void
    {
        $kitItemLineItems = $disabledKitPrices = [];
        $quoteProductsForm = $event->getForm()->get('quoteProducts')->all();
        foreach ($quoteProductsForm as $quoteProductForm) {
            /** @var QuoteProduct|null $quoteProduct */
            $quoteProduct = $quoteProductForm->getData();
            if ($quoteProduct === null || $quoteProduct->getProduct()?->isKit() !== true) {
                continue;
            }

            $formView = $quoteProductForm->createView();
            $fullName = $formView->vars['full_name'];
            $kitItemLineItems[$fullName] = $this->twig->render(
                $this->kitItemLineItemsTemplate,
                ['form' => $formView['kitItemLineItems']]
            );

            if ($quoteProduct?->getProduct()?->isKit()) {
                $disabledKitPrices[$fullName] = true;
            }
        }

        $event->getData()->offsetSet('kitItemLineItems', $kitItemLineItems);
        $event->getData()->offsetSet('disabledKitPrices', $disabledKitPrices);
    }
}

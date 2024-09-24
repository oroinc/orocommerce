<?php

namespace Oro\Bundle\ShoppingListBundle\Provider;

use Oro\Bundle\PricingBundle\Provider\CurrentCurrencyProviderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tries to get shopping list currency if current page is shopping list view
 */
class ShoppingListCurrentCurrencyProvider implements CurrentCurrencyProviderInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    #[\Override]
    public function getCurrentCurrency(): ?string
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        if ($request->attributes->get('_route') !== 'oro_shopping_list_view') {
            return null;
        }

        return $request->attributes->get('shoppingList')?->getCurrency();
    }
}

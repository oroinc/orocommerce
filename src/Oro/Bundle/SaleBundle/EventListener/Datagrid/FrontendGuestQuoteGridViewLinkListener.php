<?php

namespace Oro\Bundle\SaleBundle\EventListener\Datagrid;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Overrides the `view_link` route in the frontend quote grid
 * when the current user is anonymous (guest access).
 *
 * This is used to provide the correct view link using the `guest_access_id`
 * parameter for quotes accessible without an authenticated CustomerUser.
 */
class FrontendGuestQuoteGridViewLinkListener
{
    public function __construct(private TokenAccessorInterface $tokenAccessor)
    {
    }

    public function onBuildBefore(BuildBefore $event): void
    {
        if (!$this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken) {
            return;
        }

        $config = $event->getConfig();
        $config->offsetAddToArrayByPath('[source][query][select]', ['quote.guestAccessId']);
        $config->offsetSetByPath('[properties][view_link]', [
            'type' => 'url',
            'route' => 'oro_sale_quote_frontend_view_guest',
            'params' => ['guest_access_id' => 'guestAccessId'],
        ]);
    }
}

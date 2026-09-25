<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\OAuth2ServerBundle\Provider\SessionTransferReturnUrlProvider;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Provides the URLs of the application that started a Session Transfer to the checkout pages
 * rendered in Session Transfer mode: the URL to return to and the URL of the created order view page.
 */
class SessionTransferCheckoutUrlProvider
{
    /**
     * The path of the order view page in the application that started a Session Transfer.
     */
    private const string ORDER_VIEW_PATH = '/customer/order/view/';

    public function __construct(
        private readonly SessionTransferReturnUrlProvider $sessionTransferReturnUrlProvider
    ) {
    }

    public function getReturnUrl(): string
    {
        return $this->sessionTransferReturnUrlProvider->getReturnUrl();
    }

    public function getOrderViewUrl(Order $order): string
    {
        return $this->getReturnUrl() . self::ORDER_VIEW_PATH . $order->getId();
    }
}

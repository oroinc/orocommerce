<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

/**
 * Actualizes user currency by checkout.
 */
class ActualizeCurrency implements ActualizeCurrencyInterface
{
    public function __construct(
        private ActionExecutor $actionExecutor,
        private UserCurrencyManager $userCurrencyManager,
        private CurrencyNameHelper $currencyNameHelper
    ) {
    }

    #[\Override]
    public function execute(Checkout $checkout): void
    {
        $currentCurrency = $this->userCurrencyManager->getUserCurrency();
        $checkoutCurrency = $checkout->getCurrency();
        if (!$checkoutCurrency || $checkoutCurrency === $currentCurrency) {
            return;
        }

        $this->userCurrencyManager->saveSelectedCurrency($checkoutCurrency);
        $currencyName = $this->currencyNameHelper->getCurrencyName(
            $checkoutCurrency,
            ViewTypeProviderInterface::VIEW_TYPE_NAME
        );

        $this->actionExecutor->executeAction(
            'flash_message',
            [
                'message' => 'oro.checkout.frontend.checkout.cannot_change_currency',
                'message_parameters' => ['currency' => $currencyName],
                'type' => 'warning'
            ]
        );
    }
}

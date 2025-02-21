<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Provider\OrderLimitFormattedProviderInterface;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Layout provider for minimum and maximum order amount errors
 */
class OrderLimitLayoutProvider
{
    public function __construct(
        private OrderLimitProviderInterface $shoppingListLimitProvider,
        private OrderLimitFormattedProviderInterface $shoppingListLimitFormattedProvider,
        private TranslatorInterface $translator
    ) {
    }

    public function isOrderLimitsMet(ShoppingList $shoppingList): bool
    {
        return $this->shoppingListLimitProvider->isMinimumOrderAmountMet($shoppingList)
            && $this->shoppingListLimitProvider->isMaximumOrderAmountMet($shoppingList);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array<array{type: string, value: string}>
     */
    public function getErrors(ShoppingList $shoppingList): array
    {
        $errors = [];

        if (!$this->shoppingListLimitProvider->isMinimumOrderAmountMet($shoppingList)) {
            $errors[] = [
                'type' => 'message',
                'value' => $this->translator->trans(
                    'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_message',
                    [
                        '%amount%' => $this->shoppingListLimitFormattedProvider->getMinimumOrderAmountFormatted(),
                    ]
                )
            ];
            $errors[] = [
                'type' => 'alert',
                'value' => $this->translator->trans(
                    'oro.checkout.frontend.checkout.order_limits.minimum_order_amount_alert',
                    [
                        '%difference%' =>
                            $this->shoppingListLimitFormattedProvider->getMinimumOrderAmountDifferenceFormatted(
                                $shoppingList
                            ),
                    ]
                )
            ];
        }

        if (!$this->shoppingListLimitProvider->isMaximumOrderAmountMet($shoppingList)) {
            $errors[] = [
                'type' => 'message',
                'value' => $this->translator->trans(
                    'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_message',
                    [
                        '%amount%' => $this->shoppingListLimitFormattedProvider->getMaximumOrderAmountFormatted(),
                    ]
                )
            ];
            $errors[] = [
                'type' => 'alert',
                'value' => $this->translator->trans(
                    'oro.checkout.frontend.checkout.order_limits.maximum_order_amount_alert',
                    [
                        '%difference%' =>
                            $this->shoppingListLimitFormattedProvider->getMaximumOrderAmountDifferenceFormatted(
                                $shoppingList
                            ),
                    ]
                )
            ];
        }

        return $errors;
    }
}

<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\CustomerUserProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

/**
 * Provides methods to manage shopping list totals.
 */
class ShoppingListTotalManager
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider,
        private UserCurrencyManager $currencyManager,
        private CustomerUserProvider $customerUserProvider
    ) {
    }

    /**
     * Sets Shopping Lists Subtotal for a shopping list owner.
     *
     * @var ShoppingList[] $shoppingLists
     * @var bool           $doFlush
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function setSubtotals(array $shoppingLists, bool $doFlush = true): void
    {
        $entityManager = $this->doctrine->getManagerForClass(ShoppingListTotal::class);
        $currency = $this->currencyManager->getUserCurrency();
        $customerUser = $this->customerUserProvider->getLoggedUser(true);

        foreach ($shoppingLists as $shoppingList) {
            $isShoppingListOwner = $customerUser === $shoppingList->getCustomerUser();
            # By default, we display the totals of the owner of the shopping list.
            $shoppingListTotal = $shoppingList->getTotalForCustomerUser(
                $currency,
                $shoppingList->getCustomerUser()
            );

            # Let's try to get the subtotal for the customer user,
            # if there is no such subtotal, display the price of the owner of the shopping list.
            if ($customerUser && !$isShoppingListOwner) {
                $customerUserTotal = $shoppingList->getTotalForCustomerUser($currency, $customerUser);
                if ($customerUserTotal && $customerUserTotal->isValid()) {
                    $shoppingList->setSubtotal($customerUserTotal->getSubtotal());
                    continue;
                }
            }

            if (!$shoppingListTotal) {
                $shoppingListTotal = new ShoppingListTotal($shoppingList, $currency);
                $shoppingList->addTotal($shoppingListTotal);
                $entityManager->persist($shoppingListTotal);
            }

            if (!$shoppingListTotal->isValid() && $isShoppingListOwner) {
                $shoppingListTotal->setSubtotal(
                    $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($shoppingList, $currency)
                );
                $shoppingListTotal->setValid(true);
            }

            $shoppingList->setSubtotal($shoppingListTotal->getSubtotal());
        }

        if ($doFlush) {
            $entityManager->flush();
        }
    }

    /**
     * Sets Shopping List Subtotal for customer user.
     *
     * Caching a subtotal of the shopping list for a specific customer user.
     */
    public function setSubtotalsForCustomerUser(ShoppingList $shoppingList, CustomerUser $customerUser): void
    {
        $currency = $this->currencyManager->getUserCurrency();
        $shoppingListTotal = $shoppingList->getTotalForCustomerUser($currency, $customerUser);
        if ($shoppingListTotal && $shoppingListTotal->isValid()) {
            return;
        }

        $shoppingListTotal = $shoppingListTotal ?? new ShoppingListTotal($shoppingList, $currency);
        $shoppingListTotal->setCustomerUser($customerUser);
        $shoppingListTotal->setSubtotal(
            $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($shoppingList, $currency)
        );
        $shoppingListTotal->setValid(true);

        $entityManager = $this->doctrine->getManagerForClass(ShoppingListTotal::class);
        $entityManager->persist($shoppingListTotal);
        $entityManager->flush($shoppingListTotal);
    }

    public function recalculateTotals(ShoppingList $shoppingList, bool $doFlush): void
    {
        $this->getTotalsForCurrencies(
            $shoppingList,
            $this->currencyManager->getAvailableCurrencies(),
            true,
            $doFlush
        );
    }

    /**
     * Invalidate all subtotals and recalculate subtotal for the owner of the shopping list.
     */
    public function invalidateAndRecalculateTotals(ShoppingList $shoppingList, bool $doFlush): void
    {
        $entityManager = $this->doctrine->getManagerForClass(ShoppingListTotal::class);
        foreach ($shoppingList->getTotals() as $shoppingListTotal) {
            $shoppingListTotal->setValid(false);
            $entityManager->persist($shoppingListTotal);
        }

        $this->recalculateTotals($shoppingList, $doFlush);
    }

    public function getShoppingListTotalForCurrency(
        ShoppingList $shoppingList,
        string $currency,
        bool $doFlush = false
    ): ShoppingListTotal {
        return $this->getTotalsForCurrencies($shoppingList, [$currency], false, $doFlush)[$currency];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getTotalsForCurrencies(
        ShoppingList $shoppingList,
        array $currencies,
        bool $recalculate,
        bool $doFlush
    ): array {
        $shoppingListTotals = [];
        $customerUser = $this->customerUserProvider->getLoggedUser();
        $assignedCustomerUser = $shoppingList->getCustomerUser();

        $totals = $shoppingList->getTotalsForCustomerUser($currencies, $customerUser);
        if ($totals->isEmpty()) {
            $totals = $shoppingList->getTotalsForCustomerUser($currencies, $assignedCustomerUser);
        }
        $currencies = array_flip($currencies);
        foreach ($totals as $eachShoppingListTotal) {
            $eachCurrency = $eachShoppingListTotal->getCurrency();
            if (!isset($currencies[$eachCurrency])) {
                continue;
            }

            if ($recalculate || !$eachShoppingListTotal->isValid()) {
                $eachShoppingListTotal->setSubtotal(
                    $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($shoppingList, $eachCurrency)
                );
                $eachShoppingListTotal->setValid(true);
            }

            $shoppingListTotals[$eachCurrency] = $eachShoppingListTotal;
            unset($currencies[$eachCurrency]);
        }

        $entityManager = $this->doctrine->getManagerForClass(ShoppingListTotal::class);
        $isShoppingListManaged = $entityManager->contains($shoppingList);

        foreach ($currencies as $eachCurrency => $i) {
            $shoppingListTotal = new ShoppingListTotal($shoppingList, $eachCurrency);
            $shoppingListTotal->setCustomerUser($customerUser ?? $assignedCustomerUser);
            $shoppingListTotal->setSubtotal(
                $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($shoppingList, $eachCurrency)
            );
            $shoppingListTotal->setValid(true);
            $shoppingListTotals[$eachCurrency] = $shoppingListTotal;

            $shoppingList->addTotal($shoppingListTotals[$eachCurrency]);

            // It is possible that shopping list which came to this method is not managed dy doctrine, we should not
            // persist corresponding total in this case.
            if ($isShoppingListManaged) {
                $entityManager->persist($shoppingListTotals[$eachCurrency]);
            }
        }

        if ($doFlush && $isShoppingListManaged) {
            $entityManager->flush();
        }

        return $shoppingListTotals;
    }
}

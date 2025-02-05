<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
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
    private ?EntityManagerInterface $em = null;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider,
        private UserCurrencyManager $currencyManager,
        private CustomerUserProvider $customerUserProvider
    ) {
    }

    /**
     * @var ShoppingList[] $shoppingLists
     *
     * Sets Shopping Lists Subtotal for a shopping list owner.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function setSubtotals(array $shoppingLists, bool $doFlush = true): void
    {
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
                $this->getEntityManager()->persist($shoppingListTotal);
            }

            if (!$shoppingListTotal->isValid() && $isShoppingListOwner) {
                $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($shoppingList, $currency);
                $shoppingListTotal->setSubtotal($subtotal)->setValid(true);
            }

            $shoppingList->setSubtotal($shoppingListTotal->getSubtotal());
        }

        if ($doFlush) {
            $this->getEntityManager()->flush();
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

        $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($shoppingList, $currency);

        $shoppingListTotal = $shoppingListTotal ?? new ShoppingListTotal($shoppingList, $currency);
        $shoppingListTotal->setCustomerUser($customerUser)->setSubtotal($subtotal)->setValid(true);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($shoppingListTotal);
        $entityManager->flush($shoppingListTotal);
    }

    public function recalculateTotals(ShoppingList $shoppingList, bool $doFlush): void
    {
        $enabledCurrencies = $this->currencyManager->getAvailableCurrencies();
        $this->getTotalsForCurrencies($shoppingList, $enabledCurrencies, true, $doFlush);
    }

    /**
     * Invalidate all subtotals and recalculate subtotal for the owner of the shopping list.
     */
    public function invalidateAndRecalculateTotals(ShoppingList $shoppingList, bool $doFlush): void
    {
        foreach ($shoppingList->getTotals() as $shoppingListTotal) {
            $shoppingListTotal->setValid(false);
            $this->getEntityManager()->persist($shoppingListTotal);
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
                $subtotal = $this->lineItemNotPricedSubtotalProvider
                    ->getSubtotalByCurrency($shoppingList, $eachCurrency);

                $eachShoppingListTotal
                    ->setSubtotal($subtotal)
                    ->setValid(true);
            }

            $shoppingListTotals[$eachCurrency] = $eachShoppingListTotal;
            unset($currencies[$eachCurrency]);
        }

        $entityManager = $this->getEntityManager();
        $isShoppingListManaged = $entityManager->contains($shoppingList);

        foreach ($currencies as $eachCurrency => $i) {
            $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($shoppingList, $eachCurrency);

            $shoppingListTotals[$eachCurrency] = (new ShoppingListTotal($shoppingList, $eachCurrency))
                ->setCustomerUser($customerUser ?? $assignedCustomerUser)
                ->setSubtotal($subtotal)
                ->setValid(true);

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

    protected function getEntityManager(): ObjectManager
    {
        if (!$this->em) {
            $this->em = $this->managerRegistry->getManagerForClass(ShoppingListTotal::class);
        }

        return $this->em;
    }
}

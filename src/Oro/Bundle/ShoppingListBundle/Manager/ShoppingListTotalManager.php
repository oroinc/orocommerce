<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

/**
 * Provides methods to manage shopping list totals.
 */
class ShoppingListTotalManager
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var LineItemNotPricedSubtotalProvider
     */
    protected $lineItemNotPricedSubtotalProvider;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(
        ManagerRegistry $managerRegistry,
        LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider,
        UserCurrencyManager $currencyManager
    ) {
        $this->registry = $managerRegistry;
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
        $this->currencyManager = $currencyManager;
    }

    /**
     * Sets Shopping Lists Subtotal for user current currency
     *
     * @param ShoppingList[] $shoppingLists
     * @param bool $doFlush
     */
    public function setSubtotals(array $shoppingLists, $doFlush = true)
    {
        $currency = $this->currencyManager->getUserCurrency();
        /** @var ShoppingListTotal|null $shoppingListTotal */
        $shoppingListTotals = $this->getTotalRepository()
            ->findBy(['shoppingList' => $shoppingLists, 'currency' => $currency]);

        $shoppingListTotals = array_reduce(
            $shoppingListTotals,
            function (array $result, ShoppingListTotal $shoppingListTotal) {
                $result[$shoppingListTotal->getShoppingList()->getId()] = $shoppingListTotal;
                return $result;
            },
            []
        );

        foreach ($shoppingLists as $shoppingList) {
            $shoppingListId = $shoppingList->getId();
            $totals = $shoppingList->getTotals();
            if (!array_key_exists($shoppingListId, $shoppingListTotals) && !$totals->containsKey($currency)) {
                $shoppingListTotals[$shoppingListId] = new ShoppingListTotal($shoppingList, $currency);
                $shoppingList->addTotal($shoppingListTotals[$shoppingListId]);
                $this->getEntityManager()->persist($shoppingList);
            }
            $shoppingListTotal = $shoppingListTotals[$shoppingListId] ?? $totals->get($currency);
            if (!$shoppingListTotal->isValid()) {
                $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($shoppingList, $currency);
                $shoppingListTotal->setSubtotal($subtotal)
                    ->setValid(true);
            }
            $shoppingList->setSubtotal($shoppingListTotal->getSubtotal());
        }

        if ($doFlush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param ShoppingList $shoppingList
     * @param bool $doFlush
     */
    public function recalculateTotals(ShoppingList $shoppingList, $doFlush)
    {
        $enabledCurrencies = $this->currencyManager->getAvailableCurrencies();
        $this->getTotalsForCurrencies($shoppingList, $enabledCurrencies, true, $doFlush);
    }

    public function getShoppingListTotalForCurrency(
        ShoppingList $shoppingList,
        string $currency,
        bool $doFlush = false
    ): ShoppingListTotal {
        return $this->getTotalsForCurrencies($shoppingList, [$currency], false, $doFlush)[$currency];
    }

    private function getTotalsForCurrencies(
        ShoppingList $shoppingList,
        array $currencies,
        bool $recalculate,
        bool $doFlush
    ): array {
        $shoppingListTotals = [];
        $currencies = array_flip($currencies);
        foreach ($shoppingList->getTotals() as $eachShoppingListTotal) {
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

    /**
     * @return ObjectRepository
     */
    protected function getTotalRepository()
    {
        return $this->getEntityManager()->getRepository(ShoppingListTotal::class);
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->registry->getManagerForClass(ShoppingListTotal::class);
        }

        return $this->em;
    }
}

<?php

namespace Oro\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * @param ManagerRegistry $managerRegistry
     * @param LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider
     * @param UserCurrencyManager $currencyManager
     */
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
     * @param array $shoppingLists
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
            if (!array_key_exists($shoppingList->getId(), $shoppingListTotals)) {
                $shoppingListTotals[$shoppingList->getId()] = new ShoppingListTotal($shoppingList, $currency);
                $this->getEntityManager()->persist($shoppingListTotals[$shoppingList->getId()]);
            }
            $shoppingListTotal = $shoppingListTotals[$shoppingList->getId()];
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

    /**
     * @param ShoppingList $shoppingList
     * @param string $currency
     * @param bool $doFlush
     *
     * @return ShoppingListTotal
     */
    public function getShoppingListTotalForCurrency(
        ShoppingList $shoppingList,
        string $currency,
        bool $doFlush = false
    ): ShoppingListTotal {
        return $this->getTotalsForCurrencies($shoppingList, [$currency], false, $doFlush)[$currency];
    }

    /**
     * @param ShoppingList $shoppingList
     * @param array $currencies
     * @param bool $recalculate
     * @param bool $doFlush
     *
     * @return array
     */
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
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getTotalRepository()
    {
        return $this->getEntityManager()->getRepository(ShoppingListTotal::class);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->registry->getManagerForClass(ShoppingListTotal::class);
        }

        return $this->em;
    }
}

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
        $totals = $shoppingList->getTotals();
        $enabledCurrencies = $this->currencyManager->getAvailableCurrencies();

        foreach ($totals as $total) {
            $subtotal = $this->lineItemNotPricedSubtotalProvider
                ->getSubtotalByCurrency($shoppingList, $total->getCurrency());
            if (($key = array_search($total->getCurrency(), $enabledCurrencies, true)) !== false) {
                unset($enabledCurrencies[$key]);
            }
            $total->setSubtotal($subtotal)
                ->setValid(true);
        }

        foreach ($enabledCurrencies as $currency) {
            $total = new ShoppingListTotal($shoppingList, $currency);
            $shoppingList->addTotal($total);
            $subtotal = $this->lineItemNotPricedSubtotalProvider
                ->getSubtotalByCurrency($shoppingList, $total->getCurrency());

            $total->setSubtotal($subtotal)
                ->setValid(true);
            $this->getEntityManager()->persist($total);
        }

        if ($doFlush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $currency
     *
     * @return ShoppingListTotal
     */
    public function getShoppingListTotalForCurrency(ShoppingList $shoppingList, string $currency): ShoppingListTotal
    {
        $shoppingListTotal = null;
        foreach ($shoppingList->getTotals() as $eachShoppingListTotal) {
            if ($currency === $eachShoppingListTotal->getCurrency()) {
                $shoppingListTotal = $eachShoppingListTotal;
                break;
            }
        }

        if (!$shoppingListTotal) {
            $shoppingListTotal = new ShoppingListTotal($shoppingList, $currency);
            $shoppingList->addTotal($shoppingListTotal);
            $this->getEntityManager()->persist($shoppingListTotal);
        }

        if (!$shoppingListTotal->isValid()) {
            $subtotal = $this->lineItemNotPricedSubtotalProvider
                ->getSubtotalByCurrency($shoppingList, $currency);

            $shoppingListTotal
                ->setSubtotal($subtotal)
                ->setValid(true);
        }

        return $shoppingListTotal;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getTotalRepository()
    {
        return $this->getEntityManager()->getRepository(ShoppingListTotal::class);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|null
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->registry->getManagerForClass(ShoppingListTotal::class);
        }

        return $this->em;
    }
}

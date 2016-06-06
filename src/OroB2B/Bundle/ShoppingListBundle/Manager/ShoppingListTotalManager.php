<?php

namespace OroB2B\Bundle\ShoppingListBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

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
     * Returns Subtotal for user current currency
     *
     * @param ShoppingList $shoppingList
     * @return Subtotal
     */
    public function getSubtotal(ShoppingList $shoppingList)
    {
        $currency = $this->currencyManager->getUserCurrency();

        $shoppingListTotal = $this->getTotalRepository()
            ->findOneBy(['shoppingList' => $shoppingList, 'currency' => $currency]);

        if (!$shoppingListTotal) {
            $shoppingListTotal = new ShoppingListTotal($shoppingList, $currency);
        }
        if (!$shoppingListTotal->isValid()) {
            $subtotal = $this->lineItemNotPricedSubtotalProvider->getSubtotalByCurrency($shoppingList, $currency);
            $shoppingListTotal->setSubtotal($subtotal)
                ->setValid(true);
            $this->getEntityManager()->persist($shoppingListTotal);
            $this->getEntityManager()->flush();
        }

        return $shoppingListTotal->getSubtotal();
    }

    /**
     * @param ShoppingList $shoppingList
     * @param bool $doFlush
     */
    public function recalculateTotals(ShoppingList $shoppingList, $doFlush)
    {
        /** @var ShoppingListTotal[] $totals */
        $totals = $this->getTotalRepository()->findBy(['shoppingList' => $shoppingList]);

        foreach ($totals as $total) {
            $subtotal = $this->lineItemNotPricedSubtotalProvider
                ->getSubtotalByCurrency($shoppingList, $total->getCurrency());

            $total->setSubtotal($subtotal)
                ->setValid(true);
        }

        if ($doFlush) {
            $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')->flush();
        }
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getTotalRepository()
    {
        return $this->getEntityManager()->getRepository('OroB2BShoppingListBundle:ShoppingListTotal');
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager|null
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal');
        }

        return $this->em;
    }
}

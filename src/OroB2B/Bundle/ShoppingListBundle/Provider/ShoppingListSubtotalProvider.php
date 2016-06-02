<?php

namespace OroB2B\Bundle\ShoppingListBundle\Provider;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemNotPricedSubtotalProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListSubtotalProvider
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var LineItemNotPricedSubtotalProvider
     */
    protected $lineItemNotPricedSubtotalProvider;
    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider
     * @param UserCurrencyManager $currencyManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        LineItemNotPricedSubtotalProvider $lineItemNotPricedSubtotalProvider,
        UserCurrencyManager $currencyManager,
        TranslatorInterface $translator
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->lineItemNotPricedSubtotalProvider = $lineItemNotPricedSubtotalProvider;
        $this->currencyManager = $currencyManager;
        $this->translator = $translator;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return Subtotal
     */
    public function getSubtotal(ShoppingList $shoppingList)
    {
        $currency = $this->currencyManager->getUserCurrency();
        $shoppingListTotalEntityManager = $this
            ->managerRegistry
            ->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal');
        $shoppingListTotal = $shoppingListTotalEntityManager
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal')
            ->findOneBy(['shoppingList' => $shoppingList, 'currency' => $currency]);
        if ($shoppingListTotal) {
            if ($shoppingListTotal->isValid() === false) {
                $shoppingListTotal->setSubtotalValue($this->getAmount($shoppingList));
                $shoppingListTotal->setValid(true);
                $shoppingListTotalEntityManager->flush();
            }
        } else {
            $shoppingListTotal = new ShoppingListTotal();
            $shoppingListTotal->setValid(true);
            $shoppingListTotal->setCurrency($currency);
            $shoppingListTotal->setShoppingList($shoppingList);
            $shoppingListTotal->setSubtotalValue($this->getAmount($shoppingList));
            $shoppingListTotalEntityManager->persist($shoppingListTotal);
            $shoppingListTotalEntityManager->flush();
        }

        return $this->createSubtotal($shoppingListTotal);
    }

    /**
     * @param ShoppingListTotal $shoppingListTotal
     * @return Subtotal
     */
    protected function createSubtotal(ShoppingListTotal $shoppingListTotal)
    {
        $subtotal = new Subtotal();
        $subtotal->setAmount($shoppingListTotal->getSubtotalValue());
        $subtotal->setCurrency($shoppingListTotal->getCurrency());
        $subtotal->setVisible(false);
        $subtotal->setType(LineItemNotPricedSubtotalProvider::TYPE);
        $subtotal->setLabel($this->translator->trans(LineItemNotPricedSubtotalProvider::NAME.'.label'));

        return $subtotal;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return float
     */
    protected function getAmount(ShoppingList $shoppingList)
    {
        return $this->lineItemNotPricedSubtotalProvider->getSubtotal($shoppingList)->getAmount();
    }
}

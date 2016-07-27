<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListProductsUnitsDataProvider
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var PriceListRequestHandler
     */
    protected $requestHandler;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @param Registry $registry
     * @param PriceListRequestHandler $requestHandler
     * @param UserCurrencyManager $userCurrencyManager
     */
    public function __construct(
        Registry $registry,
        PriceListRequestHandler $requestHandler,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->registry = $registry;
        $this->requestHandler = $requestHandler;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array|null
     */
    public function getProductsUnits(ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            return null;
        }

        $products = $shoppingList->getLineItems()->map(
            function (LineItem $lineItem) {
                return $lineItem->getProduct();
            }
        );

        return $this->registry->getManagerForClass('OroB2BProductBundle:ProductUnit')
            ->getRepository('OroB2BProductBundle:ProductUnit')
            ->getProductsUnits($products->toArray());
    }
}

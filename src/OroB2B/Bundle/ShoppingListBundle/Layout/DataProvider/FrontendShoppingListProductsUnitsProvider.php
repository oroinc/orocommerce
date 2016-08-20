<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListProductsUnitsProvider
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

        return $this->registry->getManagerForClass('OroProductBundle:ProductUnit')
            ->getRepository('OroProductBundle:ProductUnit')
            ->getProductsUnits($products->toArray());
    }
}

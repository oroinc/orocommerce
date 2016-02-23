<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\PricingBundle\Model\FrontendPriceListRequestHandler;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListProductsUnitsDataProvider implements DataProviderInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var FrontendPriceListRequestHandler
     */
    protected $requestHandler;

    /**
     * @var UserCurrencyProvider
     */
    protected $userCurrencyProvider;

    /**
     * @param Registry $registry
     * @param FrontendPriceListRequestHandler $requestHandler
     * @param UserCurrencyProvider $userCurrencyProvider
     */
    public function __construct(
        Registry $registry,
        FrontendPriceListRequestHandler $requestHandler,
        UserCurrencyProvider $userCurrencyProvider
    ) {
        $this->registry = $registry;
        $this->requestHandler = $requestHandler;
        $this->userCurrencyProvider = $userCurrencyProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $shoppingList = $context->data()->get('shoppingList');

        if (!$shoppingList) {
            return null;
        }

        return $this->getProductsUnits($shoppingList);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array|null
     */
    protected function getProductsUnits(ShoppingList $shoppingList)
    {
        $priceList = $this->requestHandler->getPriceList();

        $products = $shoppingList->getLineItems()->map(
            function (LineItem $lineItem) {
                return $lineItem->getProduct();
            }
        );

        return $this->registry->getManagerForClass('OroB2BPricingBundle:ProductPrice')
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->getProductsUnitsByPriceList($priceList, $products, $this->userCurrencyProvider->getUserCurrency());
    }
}

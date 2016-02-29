<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Doctrine\Common\Collections\Collection;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListProductsPricesDataProvider implements DataProviderInterface
{
    /**
     * @var ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var UserCurrencyProvider
     */
    protected $userCurrencyProvider;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param SecurityFacade $securityFacade
     * @param UserCurrencyProvider $userCurrencyProvider
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        SecurityFacade $securityFacade,
        UserCurrencyProvider $userCurrencyProvider
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->securityFacade = $securityFacade;
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

        return $this->getProductsPrices($shoppingList);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array|null
     */
    protected function getProductsPrices(ShoppingList $shoppingList)
    {
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        $lineItems = $shoppingList->getLineItems();
        $productsPriceCriteria = $this->getProductsPricesCriteria($lineItems);

        $prices = $this->productPriceProvider->getMatchedPrices($productsPriceCriteria);

        $result = [];
        foreach ($prices as $key => $price) {
            $identifier = explode('-', $key);
            $productId = reset($identifier);
            $result[$productId] = $price;
        }

        return $result;
    }

    /**
     * @param Collection|LineItem[] $lineItems
     * @return array
     */
    protected function getProductsPricesCriteria(Collection $lineItems)
    {
        $productsPricesCriteria = [];
        foreach ($lineItems as $lineItem) {
            $productsPricesCriteria[] = new ProductPriceCriteria(
                $lineItem->getProduct(),
                $lineItem->getProductUnit(),
                $lineItem->getQuantity(),
                $this->userCurrencyProvider->getUserCurrency()
            );
        }

        return $productsPricesCriteria;
    }
}

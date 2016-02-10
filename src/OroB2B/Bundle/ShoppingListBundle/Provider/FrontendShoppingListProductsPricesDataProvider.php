<?php

namespace OroB2B\Bundle\ShoppingListBundle\Provider;

use Doctrine\Common\Collections\Collection;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\PricingBundle\Model\ProductPriceCriteria;
use OroB2B\Bundle\PricingBundle\Provider\ProductPriceProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListProductsPricesDataProvider implements DataProviderInterface
{
    /**
     * @var FormAccessor
     */
    protected $data;

    /**
     * @var ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var string
     */
    protected $shoppingListClass;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ProductPriceProvider $productPriceProvider, SecurityFacade $securityFacade)
    {
        $this->productPriceProvider = $productPriceProvider;
        $this->securityFacade = $securityFacade;
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

        if (!$this->data) {
            $this->data = $this->getProductsPrices($shoppingList);
        }
        return $this->data;
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
     * @param Collection $lineItems
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
                'USD'
            );
        }

        return $productsPricesCriteria;
    }
}

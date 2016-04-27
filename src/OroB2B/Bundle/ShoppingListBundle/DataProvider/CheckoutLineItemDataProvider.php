<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Component\Checkout\DataProvider\AbstractCheckoutProvider;

class CheckoutLineItemDataProvider extends AbstractCheckoutProvider
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var UserCurrencyProvider
     */
    protected $currencyProvider;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param FrontendProductPricesDataProvider $frontendProductPricesDataProvider
     * @param ManagerRegistry $registry
     */
    public function __construct(
        FrontendProductPricesDataProvider $frontendProductPricesDataProvider,
        ManagerRegistry $registry
    ) {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
        $this->registry = $registry;
    }


    /**
     * @param ShoppingList $entity
     * @return array
     */
    public function prepareData($entity)
    {
        /** @var LineItemRepository $repository */
        $repository = $this->registry->getManagerForClass('OroB2BShoppingListBundle:LineItem')
            ->getRepository('OroB2BShoppingListBundle:LineItem');
        $lineItems = $repository->getItemsWithProductByShoppingList($entity);

        $shoppingListPrices = $this->frontendProductPricesDataProvider->getProductsMatchedPrice($lineItems);
        $data = [];
        foreach ($lineItems as $lineItem) {
            $data[] = [
                'product' => $lineItem->getProduct(),
                'productSku' => $lineItem->getProductSku(),
                'quantity' => $lineItem->getQuantity(),
                'productUnit' => $lineItem->getProductUnit(),
                'productUnitCode' => $lineItem->getProductUnitCode(),
                'price' => $shoppingListPrices[$lineItem->getProduct()->getId()],
            ];
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function isEntitySupported($entity)
    {
        return $entity instanceof ShoppingList;
    }
}

<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Checkout\DataProvider\AbstractCheckoutProvider;

class CheckoutLineItemDataProvider extends AbstractCheckoutProvider
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

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
        $repository = $this->registry->getManagerForClass('OroShoppingListBundle:LineItem')
            ->getRepository('OroShoppingListBundle:LineItem');
        $lineItems = $repository->getItemsWithProductByShoppingList($entity);

        $shoppingListPrices = $this->frontendProductPricesDataProvider->getProductsMatchedPrice($lineItems);
        $data = [];
        foreach ($lineItems as $lineItem) {
            $unitCode = $lineItem->getProductUnitCode();
            $price = null;
            if (isset($shoppingListPrices[$lineItem->getProduct()->getId()][$unitCode])) {
                $price = $shoppingListPrices[$lineItem->getProduct()->getId()][$unitCode];
            }
            $data[] = [
                'product' => $lineItem->getProduct(),
                'productSku' => $lineItem->getProductSku(),
                'quantity' => $lineItem->getQuantity(),
                'productUnit' => $lineItem->getProductUnit(),
                'productUnitCode' => $unitCode,
                'price' => $price,
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

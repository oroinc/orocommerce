<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;

class FrontendShoppingListProductsPricesDataProvider implements DataProviderInterface
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $productPriceProvider;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param FrontendProductPricesDataProvider $productPriceProvider
     * @param ManagerRegistry $registry
     */
    public function __construct(
        FrontendProductPricesDataProvider $productPriceProvider,
        ManagerRegistry $registry
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->registry = $registry;
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
        $shoppingList = $context->data()->get('entity');
        if (!$shoppingList) {
            return null;
        }
        /** @var LineItemRepository $repository */
        $repository = $this->registry->getManagerForClass('OroB2BShoppingListBundle:LineItem')
            ->getRepository('OroB2BShoppingListBundle:LineItem');
        $lineItems = $repository->getItemsWithProductByShoppingList($shoppingList);

        return $this->productPriceProvider->getProductsMatchedPrice($lineItems);
    }
}

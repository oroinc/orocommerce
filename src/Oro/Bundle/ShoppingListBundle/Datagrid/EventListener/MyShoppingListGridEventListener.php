<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Populates line item records by required data.
 */
class MyShoppingListGridEventListener
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var FrontendProductPricesDataProvider */
    private $productPricesDataProvider;

    /** @var ProductPriceFormatter */
    private $productPriceFormatter;

    /** @var ConfigurableProductProvider */
    private $configurableProductProvider;

    /**
     * @param ManagerRegistry $registry
     * @param FrontendProductPricesDataProvider $productPricesDataProvider
     * @param ProductPriceFormatter $productPriceFormatter
     * @param ConfigurableProductProvider $configurableProductProvider
     */
    public function __construct(
        ManagerRegistry $registry,
        FrontendProductPricesDataProvider $productPricesDataProvider,
        ProductPriceFormatter $productPriceFormatter,
        ConfigurableProductProvider $configurableProductProvider
    ) {
        $this->registry = $registry;
        $this->productPricesDataProvider = $productPricesDataProvider;
        $this->productPriceFormatter = $productPriceFormatter;
        $this->configurableProductProvider = $configurableProductProvider;
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event): void
    {
        $shoppingListId = $event->getDatagrid()
            ->getParameters()
            ->get('shopping_list_id');

        $shoppingList = $this->registry->getManagerForClass(ShoppingList::class)
            ->getRepository(ShoppingList::class)
            ->find($shoppingListId);

        if (!$shoppingList) {
            return;
        }

        $lineItems = $shoppingList->getLineItems()->toArray();

        $productPrices = $this->productPricesDataProvider->getProductsAllPrices($lineItems);
        $matchedPrices = $this->productPricesDataProvider->getProductsMatchedPrice($lineItems);
        $allPrices = $this->productPriceFormatter->formatProducts($productPrices);
        $configurableProducts = $this->configurableProductProvider->getProducts($lineItems);

        foreach ($event->getRecords() as $record) {
            $record->setValue(
                'lineItems',
                array_filter(
                    array_map(
                        static function (int $id) use ($lineItems) {
                            foreach ($lineItems as $lineItem) {
                                if ($lineItem->getId() === $id) {
                                    return $lineItem;
                                }
                            }

                            return null;
                        },
                        explode(',', $record->getValue('lineItemIds'))
                    )
                )
            );
            $record->setValue('matchedPrices', $matchedPrices);
            $record->setValue('allPrices', $allPrices);
            $record->setValue('configurableProducts', $configurableProducts);
        }
    }
}

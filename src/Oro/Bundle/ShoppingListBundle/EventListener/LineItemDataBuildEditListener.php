<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\ProductUnitsListProvider;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Adds to LineItemDataBuildEvent the data needed for shopping list edit page.
 */
class LineItemDataBuildEditListener
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var ProductUnitsListProvider */
    private $productUnitsListProvider;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param ProductUnitsListProvider $productUnitsListProvider
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, ProductUnitsListProvider $productUnitsListProvider)
    {
        $this->urlGenerator = $urlGenerator;
        $this->productUnitsListProvider = $productUnitsListProvider;
    }

    /**
     * @param LineItemDataBuildEvent $event
     */
    public function onLineItemData(LineItemDataBuildEvent $event): void
    {
        if (!$this->isApplicable($event)) {
            return;
        }

        foreach ($event->getLineItems() as $lineItem) {
            $lineItemId = $lineItem->getId();
            $lineItemData = $event->getDataForLineItem($lineItemId);

            // Units list are needed for units dropdown.
            $lineItemData['units'] = $this->productUnitsListProvider
                ->getProductUnitsList($lineItem->getProduct(), $lineItem->getProductUnit());

            // Adds delete url.
            $deleteLinkParameters = ['id' => $lineItemId];
            if ($lineItem->getParentProduct()) {
                $deleteLinkParameters['onlyCurrent'] = true;
            }
            $lineItemData['deleteLink'] = $this->urlGenerator->generate(
                'oro_api_shopping_list_frontend_delete_line_item',
                $deleteLinkParameters
            );

            $event->setDataForLineItem($lineItemId, $lineItemData);
        }
    }

    /**
     * @param LineItemDataBuildEvent $event
     * @return bool
     */
    protected function isApplicable(LineItemDataBuildEvent $event): bool
    {
        /** @var Datagrid $datagrid */
        $datagrid = $event->getContext()['datagrid'] ?? null;

        return $datagrid && $datagrid->getName() === 'frontend-customer-user-shopping-list-edit-grid';
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Manager\MultiShipping\CheckoutLineItemsShippingManager;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItem\LineItemShippingMethodsProviderInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid\LineItemsDataOnResultAfterListener;

/**
 * Adds available line item shipping methods to the datagrid records
 * and adds new column to display these values.
 */
class FrontendCheckoutLineItemsDatagridEventListener
{
    private const LINE_ITEMS_SHIPPING_PARAMETER = 'use_line_items_shipping';
    private const SHIPPING_METHODS_FIELD_NAME = 'shippingMethods';
    private const LINE_ITEM_ID_PROPERTY = 'lineItemId';
    private const CURRENT_SHIPPING_METHODS_PROPERTY = 'currentShippingMethod';
    private const CURRENT_SHIPPING_METHODS_TYPE_PROPERTY = 'currentShippingMethodType';

    private ManagerRegistry $managerRegistry;
    private LineItemShippingMethodsProviderInterface $shippingMethodProvider;
    private CheckoutLineItemsShippingManager $lineItemsShippingManager;

    public function __construct(
        ManagerRegistry $managerRegistry,
        LineItemShippingMethodsProviderInterface $shippingMethodProvider,
        CheckoutLineItemsShippingManager $lineItemsShippingManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->lineItemsShippingManager = $lineItemsShippingManager;
    }

    /**
     * Attaches new column to datagrid which is responsible for shipping methods selections rendering.
     */
    public function onBuildBefore(BuildBefore $event): void
    {
        if (!$this->useLineItemsShippingMethods($event->getDatagrid())) {
            return;
        }

        $config = $event->getConfig();
        $config->addColumn(self::SHIPPING_METHODS_FIELD_NAME, [
            'label' => 'oro.checkout.order_summary.shipping_methods',
            'frontend_type' => 'shipping-methods'
        ]);
        $config->addProperty(self::CURRENT_SHIPPING_METHODS_PROPERTY, []);
        $config->addProperty(self::CURRENT_SHIPPING_METHODS_TYPE_PROPERTY, []);
        $config->addProperty(self::LINE_ITEM_ID_PROPERTY, []);
    }

    /**
     * Adds shipping methods for line items to grid's result records.
     */
    public function onResultAfter(OrmResultAfter $event): void
    {
        if (!$this->useLineItemsShippingMethods($event->getDatagrid())) {
            return;
        }

        $records = $event->getRecords();

        foreach ($records as $record) {
            $id = $record->getValue('id');
            $lineItems = $record->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS) ?? [];
            $lineItems = array_filter($lineItems, static fn ($lineItem) => $lineItem->getId() === $id);

            $lineItem = !empty($lineItems) ? reset($lineItems) : $this->findLineItem($id);

            if (!$lineItem) {
                return;
            }

            $lineItemId = $this->lineItemsShippingManager->getLineItemIdentifier($lineItem);
            $record->setValue(self::LINE_ITEM_ID_PROPERTY, $lineItemId);

            $lineItemShippingMethods = $this->shippingMethodProvider->getAvailableShippingMethods($lineItem);

            $record->setValue(self::SHIPPING_METHODS_FIELD_NAME, $lineItemShippingMethods);
            $record->setValue(self::CURRENT_SHIPPING_METHODS_PROPERTY, $lineItem->getShippingMethod());
            $record->setValue(self::CURRENT_SHIPPING_METHODS_TYPE_PROPERTY, $lineItem->getShippingMethodType());
        }
    }

    private function findLineItem(int $id): ?object
    {
        return $this->managerRegistry->getRepository(CheckoutLineItem::class)->find($id);
    }

    private function useLineItemsShippingMethods(DatagridInterface $datagrid): bool
    {
        return $datagrid->getParameters()->get(self::LINE_ITEMS_SHIPPING_PARAMETER, false);
    }
}

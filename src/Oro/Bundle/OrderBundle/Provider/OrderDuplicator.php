<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Event\OrderDuplicateAfterEvent;
use Oro\Component\Duplicator\DuplicatorFactory;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Duplicates given order to new one.
 * Note. Cannot correctly duplicate order with suborders.
 */
class OrderDuplicator
{
    public function __construct(
        private readonly DuplicatorFactory $duplicatorFactory,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function duplicate(Order $order): Order
    {
        $duplicator = $this->duplicatorFactory->create();

        $duplicatedOrder = $duplicator->duplicate(
            $order,
            [
                [['setNull'], ['propertyName', ['id']]],
                [['setNull'], ['propertyName', ['createdAt']]],
                [['setNull'], ['propertyName', ['updatedAt']]],
                [['setNull'], ['propertyName', ['updatedAtSet']]],
                [['setNull'], ['propertyName', ['identifier']]],
                [['setNull'], ['propertyName', ['parent']]],

                [['keep'], ['propertyName', ['customerUser']]],
                [['keep'], ['propertyName', ['customer']]],
                [['keep'], ['propertyName', ['owner']]],
                [['keep'], ['propertyName', ['organization']]],
                [['keep'], ['propertyName', ['poNumber']]],
                [['keep'], ['propertyName', ['currency']]],
                [['keep'], ['propertyName', ['createdBy']]],
                [['keep'], ['propertyName', ['shipUntil']]],
                [['keep'], ['propertyName', ['subtotal']]],
                [['keep'], ['propertyName', ['subtotalDiscounts']]],
                [['keep'], ['propertyName', ['subtotalWithDiscountsCurrency']]],
                [['keep'], ['propertyName', ['subtotalWithDiscountsValue']]],
                [['keep'], ['propertyName', ['subtotalWithDiscounts']]],
                [['keep'], ['propertyName', ['subtotalCurrency']]],
                [['keep'], ['propertyName', ['subtotalValue']]],
                [['keep'], ['propertyName', ['baseSubtotalValue']]],
                [['keep'], ['propertyName', ['total']]],
                [['keep'], ['propertyName', ['totalCurrency']]],
                [['keep'], ['propertyName', ['totalValue']]],
                [['keep'], ['propertyName', ['baseTotalValue']]],
                [['keep'], ['propertyName', ['website']]],
                [['keep'], ['propertyName', ['shippingMethod']]],
                [['keep'], ['propertyName', ['shippingMethodType']]],
                [['keep'], ['propertyName', ['estimatedShippingCostAmount']]],
                [['keep'], ['propertyName', ['overriddenShippingCostAmount']]],
                [['keep'], ['propertyName', ['sourceEntityClass']]],
                [['keep'], ['propertyName', ['sourceEntityId']]],
                [['keep'], ['propertyName', ['sourceEntityIdentifier']]],
                [['keep'], ['propertyName', ['totalDiscountsAmount']]],
                [['keep'], ['propertyName', ['totalDiscounts']]],
                [['keep'], ['propertyName', ['updatedAtSet']]],

                [['emptyCollection'], ['propertyName', ['lineItems']]],
                [['emptyCollection'], ['propertyName', ['shippingTrackings']]],
                [['collection'], ['propertyType', [Collection::class]]],

                [['setNull'], ['property', [OrderAddress::class, 'id']]],
                [['setNull'], ['property', [OrderAddress::class, 'created']]],
                [['setNull'], ['property', [OrderAddress::class, 'updated']]],
                [['keep'], ['property', [OrderAddress::class, 'country']]],
                [['keep'], ['property', [OrderAddress::class, 'region']]],
                [['keep'], ['property', [OrderAddress::class, 'customerAddress']]],
                [['keep'], ['property', [OrderAddress::class, 'customerUserAddress']]],
            ]
        );

        foreach ($order->getLineItems() as $lineItem) {
            $duplicatedLineItem = $duplicator->duplicate(
                $lineItem,
                [
                    [['emptyCollection'], ['propertyName', ['drafts']]],
                    [['emptyCollection'], ['propertyName', ['orders']]],

                    [['collection'], ['propertyType', [Collection::class]]],

                    [['shallowCopy'], ['propertyType', [Price::class]]],

                    [['setNull'], ['propertyName', ['id']]],
                    [['setNull'], ['propertyName', ['createdAt']]],
                    [['setNull'], ['propertyName', ['updatedAt']]],
                    [['setNull'], ['propertyName', ['updatedAtSet']]],

                    [['keep'], ['propertyName', ['product']]],
                    [['keep'], ['propertyName', ['parentProduct']]],
                    [['keep'], ['propertyName', ['productVariantFields']]],
                    [['keep'], ['propertyName', ['freeFormProduct']]],
                    [['keep'], ['propertyName', ['productUnit']]],
                    [['keep'], ['propertyName', ['productUnitCode']]],
                    [['keep'], ['propertyName', ['currency']]],

                    [['keep'], ['property', [OrderProductKitItemLineItem::class, 'product']]],
                    [['keep'], ['property', [OrderProductKitItemLineItem::class, 'kitItem']]],
                    [['keep'], ['property', [OrderProductKitItemLineItem::class, 'productUnit']]],
                ]
            );
            $duplicatedOrder->addLineItem($duplicatedLineItem);
        }

        // Clears extended entity storage as the duplicator component does not support extended entity fields.
        $duplicatedOrder->getStorage()->exchangeArray([]);

        $this->eventDispatcher->dispatch(new OrderDuplicateAfterEvent($order, $duplicatedOrder));

        return $duplicatedOrder;
    }
}

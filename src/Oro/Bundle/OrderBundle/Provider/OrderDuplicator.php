<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Component\Duplicator\DuplicatorFactory;

/**
 * Duplicates given order to new one.
 * Note. Cannot correctly duplicate order with suborders.
 */
class OrderDuplicator
{
    private DuplicatorFactory $duplicatorFactory;

    public function __construct(DuplicatorFactory $duplicatorFactory)
    {
        $this->duplicatorFactory = $duplicatorFactory;
    }

    public function duplicate(Order $order): Order
    {
        return $this->duplicatorFactory->create()->duplicate(
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

                [['collection'], ['propertyName', ['subOrders']]],
                [['collection'], ['propertyName', ['discounts']]],
                [['emptyCollection'], ['propertyName', ['shippingTrackings']]],
                [['collection'], ['propertyType', [Collection::class]]],

                [['setNull'], ['property', [OrderLineItem::class, 'id']]],
                [['setNull'], ['property', [OrderLineItem::class, 'createdAt']]],
                [['setNull'], ['property', [OrderLineItem::class, 'updatedAt']]],
                [['setNull'], ['property', [OrderLineItem::class, 'updatedAtSet']]],
                [['keep'], ['property', [OrderLineItem::class, 'product']]],
                [['keep'], ['property', [OrderLineItem::class, 'parentProduct']]],
                [['keep'], ['property', [OrderLineItem::class, 'productVariantFields']]],
                [['keep'], ['property', [OrderLineItem::class, 'freeFormProduct']]],
                [['keep'], ['property', [OrderLineItem::class, 'productUnit']]],
                [['keep'], ['property', [OrderLineItem::class, 'productUnitCode']]],
                [['keep'], ['property', [OrderLineItem::class, 'currency']]],

                [['setNull'], ['property', [OrderAddress::class, 'id']]],
                [['setNull'], ['property', [OrderAddress::class, 'created']]],
                [['setNull'], ['property', [OrderAddress::class, 'updated']]],
                [['keep'], ['property', [OrderAddress::class, 'country']]],
                [['keep'], ['property', [OrderAddress::class, 'region']]],
                [['keep'], ['property', [OrderAddress::class, 'customerAddress']]],
                [['keep'], ['property', [OrderAddress::class, 'customerUserAddress']]],

                [['keep'], ['property', [OrderProductKitItemLineItem::class, 'product']]]
            ]
        );
    }
}

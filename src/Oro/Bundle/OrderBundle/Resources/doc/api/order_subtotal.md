# Oro\Bundle\OrderBundle\Api\Model\OrderSubtotal

## ACTIONS

### get_list

Retrieve a collection of order subtotals by order ID.

{@inheritdoc}

**Note:** It is required to specify the **order** filter in a request.

## FIELDS

### id

The unique identifier of a resource.

### orderSubtotalType

The order subtotal type.

### label

The order subtotal label.

### order

The order.

### amount

The amount of the order subtotal.

### signedAmount

The signed amount of the order subtotal (if operation is subtraction than negative amount is returned, otherwise positive amount is returned).

### currency

The order subtotal currency.

### priceList

The price list.

### visible

The visibility of order subtotal in total block.

### data

The extra data.

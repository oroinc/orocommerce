# Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem

## ACTIONS

### get

Retrieve a specific order product kit item line item record.

{@inheritdoc}

### get_list

Retrieve a collection of order product kit item line item records.

{@inheritdoc}

### create

This resource describes an order product kit item line item entity. It cannot be used independently to create order product kit item line item.
Order product kit item line items can only be created together with an order via the order creation API resource.

{@inheritdoc}

## FIELDS

### price

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

**This value can be specified in the request. The value is compared to the calculated price
and if the prices are not equal, the `price match constraint` validation error is returned.
This way you can ensure that the price that was displayed to the customer during checkout
is the same as in the created order.**

### currency

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

**This value can be specified in the request. The value is compared to the current currency
and if the currencies are not equal, the `currency match constraint` validation error is returned.
This way you can ensure that the currency that was displayed to the customer during checkout
is the same as in the created order.**

### optional

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productId

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productName

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productUnit

#### create

{@inheritdoc}

**The required field.**

### productUnitCode

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productUnitPrecision

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### quantity

#### create

{@inheritdoc}

**The required field.**

### kitItemId

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### kitItemLabel

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### minimumQuantity

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### maximumQuantity

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### product

#### create

{@inheritdoc}

**Note:**
This value can be omitted if the **productSku** field is specified in the request.

## SUBRESOURCES

### kitItem

#### get_subresource

Retrieve a record of kit item assigned to a specific order product kit item line item record.

#### get_relationship

Retrieve the ID of kit item record assigned to a specific order product kit item line item record.

### lineItem

#### get_subresource

Retrieve a record of line item assigned to a specific order product kit item line item record.

#### get_relationship

Retrieve the ID of line item record assigned to a specific order product kit item line item record.

### product

#### get_subresource

Retrieve a record of product assigned to a specific order product kit item line item record.

#### get_relationship

Retrieve the ID of product record assigned to a specific order product kit item line item record.

### productUnit

#### get_subresource

Retrieve a record of product unit assigned to a specific order product kit item line item record.

#### get_relationship

Retrieve the ID of product unit record assigned to a specific order product kit item line item record.

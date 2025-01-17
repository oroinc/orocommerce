# Oro\Bundle\OrderBundle\Entity\OrderLineItem

## ACTIONS

### get

Retrieve a specific order line item record.

{@inheritdoc}

### get_list

Retrieve a collection of order line item records.

{@inheritdoc}

### create

This resource describes an order line item entity. It cannot be used independently to create line items.
Line items can only be created together with an order via the order creation API resource.

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

### freeFormProduct

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

### quantity

#### create

{@inheritdoc}

**The required field.**

### product

#### create

{@inheritdoc}

**Note:**
This value can be omitted if the **productSku** field is specified in the request.

### kitItemLineItems

{@inheritdoc}

#### create

**The field is required if base product is a kit**

### checksum

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### orders

#### get_subresource

Retrieve the orders a specific line item record is assigned to.

#### get_relationship

Retrieve the IDs of the orders which a specific line item record is assigned to.

### parentProduct

#### get_subresource

Retrieve a record of parent product assigned to a specific line item record.

#### get_relationship

Retrieve ID of parent product record assigned to a specific line item record.

### product

#### get_subresource

Retrieve a record of product assigned to a specific line item record.

#### get_relationship

Retrieve ID of product record assigned to a specific line item record.

### productUnit

#### get_subresource

Retrieve a record of product unit assigned to a specific line item record.

#### get_relationship

Retrieve ID of product unit record assigned to a specific line item record.

### kitItemLineItems

#### get_subresource

Retrieve a list of order product kit item line item records assigned to a specific line item record.

#### get_relationship

Retrieve IDs of order product kit item line item records assigned to a specific line item record.

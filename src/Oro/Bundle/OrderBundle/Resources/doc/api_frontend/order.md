# Oro\Bundle\OrderBundle\Entity\Order

## ACTIONS

### get

Retrieve a specific order record.

{@inheritdoc}

### get_list

Retrieve a collection of order records.

{@inheritdoc}

## FIELDS

### discounts

An array of the discounts that are applied to the order (including the discounts per line items, per order, and shipping discounts).

Each element of the array is an object with the following properties:

The **type** property is a string contains the discount type, e.g. `order`, `promotion.order`, `promotion.shipping`, etc.

The **description** property is a string contains a human-readable description provided for the discount.

The **amount** property is a string contains the monetary value of the discount.

Example of data: **\[{"type": "order", "description": "discount 1", "amount": "123.4500"}, {"type": "promotion.shipping", "description": "discount 2", "amount": "15.000"}\]**

### shippingTrackings

An array of the shipping tracking records.

Each element of the array is an object with two properties, **method** and **number**.

The **method** property is a string contains the shipping tracking method.

The **number** property is a string contains the shipping tracking number.

Example of data: **\[{"method": "UPS", "number": "UP243566"}, {"method": "DHL", "number": "123-12345678"}\]**

### shippingMethod

An object represents the selected shipping method.

The object has two properties, **code** and **label**.

The **code** property is a string contains the shipping method code.

The **label** property is a string contains the shipping method label.

Example of data: **{"code": "ups_3", "label": "UPS"}**

### shippingCostAmount

The shipping cost for the order.

### paymentStatus

An object represents the payment status that indicates whether the order is already paid in full, the payment for the order is authorized, etc.

The object has two properties, **code** and **label**.

The **code** property is a string contains the payment status code. Possible values are `full`, `partially`, `invoiced`, `authorized`, `declined`, `pending`.

The **label** property is a string contains the payment status label.

Example of data: **{"code": "pending", "label": "Pending payment"}**

### paymentMethod

An array of the selected payment methods.

Each element of the array is an object with two properties, **code** and **label**.

The **code** property is a string contains the payment method code.

The **label** property is a string contains the payment method label.

Example of data: **\[{"code": "payment_term_1", "label": "Payment Term"}, {"method": "money_order_5", "number": "Money Order"}\]**

## SUBRESOURCES

### billingAddress

#### get_subresource

Retrieve a record of address assigned to a specific order record.

#### get_relationship

Retrieve ID of address record assigned to a specific order record.

### customer

#### get_subresource

Retrieve a record of customer assigned to a specific order record.

#### get_relationship

Retrieve ID of customer record assigned to a specific order record.

### customerUser

#### get_subresource

Retrieve a record of customer user assigned to a specific order record.

#### get_relationship

Retrieve ID of customer user record assigned to a specific order record.

### lineItems

#### get_subresource

Retrieve a record of line item assigned to a specific order record.

#### get_relationship

Retrieve IDs of line item records assigned to a specific order record.

### shippingAddress

#### get_subresource

Retrieve a record of address assigned to a specific order record.

#### get_relationship

Retrieve ID of address record assigned to a specific order record.

### paymentTerm

#### get_subresource

Retrieve a record of payment term assigned to a specific order record.

#### get_relationship

Retrieve ID of payment term record assigned to a specific order record.

# Oro\Bundle\OrderBundle\Entity\Order

## ACTIONS

### get

Retrieve a specific order record.

{@inheritdoc}

### get_list

Retrieve a collection of order records.

{@inheritdoc}

### create

Create a new order using **Payment Term** payment method.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orders",
    "relationships": {
      "billingAddress": {
        "data": {
          "type": "orderaddresses",
          "id": "billing1"
        }
      },
      "shippingAddress": {
        "data": {
          "type": "orderaddresses",
          "id": "shipping1"
        }
      },
      "lineItems": {
        "data": [
          {
            "type": "orderlineitems",
            "id": "item1"
          }
        ]
      }
    }
  },
  "included": [
    {
      "type": "orderaddresses",
      "id": "billing1",
      "relationships": {
        "customerAddress": {
          "data": {
            "type": "customeraddresses",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "orderaddresses",
      "id": "shipping1",
      "relationships": {
        "customerAddress": {
          "data": {
            "type": "customeraddresses",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "orderlineitems",
      "id": "item1",
      "attributes": {
        "quantity": 10
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "1"
          }
        },
        "productUnit": {
          "data": {
            "type": "productunits",
            "id": "item"
          }
        }
      }
    }
  ]
}
```
{@/request}

A visitor can also create an order when the "Guest Checkout" feature is enabled.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orders",
    "relationships": {
      "customerUser": {
        "data": {
          "type": "customerusers",
          "id": "guest1"
        }
      },
      "billingAddress": {
        "data": {
          "type": "orderaddresses",
          "id": "billing1"
        }
      },
      "shippingAddress": {
        "data": {
          "type": "orderaddresses",
          "id": "shipping1"
        }
      },
      "lineItems": {
        "data": [
          {
            "type": "orderlineitems",
            "id": "item1"
          }
        ]
      }
    }
  },
  "included": [
    {
      "type": "customerusers",
      "id": "guest1",
      "attributes": {
        "email": "AmandaCole@example.org"
      }
    },
    {
      "type": "orderaddresses",
      "id": "billing1",
      "attributes": {
        "street": "1215 Caldwell Road",
        "city": "Rochester",
        "postalCode": "14608",
        "firstName": "Amanda",
        "lastName": "Cole"
      },
      "relationships": {
        "country": {
          "data": {
            "type": "countries",
            "id": "US"
          }
        },
        "region": {
          "data": {
            "type": "regions",
            "id": "US-NY"
          }
        }
      }
    },
    {
      "type": "orderaddresses",
      "id": "shipping1",
      "attributes": {
        "street": "1215 Caldwell Road",
        "city": "Rochester",
        "postalCode": "14608",
        "firstName": "Amanda",
        "lastName": "Cole"
      },
      "relationships": {
        "country": {
          "data": {
            "type": "countries",
            "id": "US"
          }
        },
        "region": {
          "data": {
            "type": "regions",
            "id": "US-NY"
          }
        }
      }
    },
    {
      "type": "orderlineitems",
      "id": "item1",
      "attributes": {
        "quantity": 10
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "1"
          }
        },
        "productUnit": {
          "data": {
            "type": "productunits",
            "id": "item"
          }
        }
      }
    }
  ]
}
```
{@/request}

## FIELDS

### discounts

An array of the discounts that are applied to the order (including the discounts per line items, per order, and shipping discounts).

Each element of the array is an object with the following properties:

The **type** property is a string contains the discount type, e.g. `order`, `promotion.order`, `promotion.shipping`, etc.

The **description** property is a string contains a human-readable description provided for the discount.

The **amount** property is a string contains the monetary value of the discount.

Example of data: **\[{"type": "order", "description": "discount 1", "amount": "123.4500"}, {"type": "promotion.shipping", "description": "discount 2", "amount": "15.000"}\]**

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### shippingTrackings

An array of the shipping tracking records.

Each element of the array is an object with two properties, **method** and **number**.

The **method** property is a string contains the shipping tracking method.

The **number** property is a string contains the shipping tracking number.

Example of data: **\[{"method": "UPS", "number": "UP243566"}, {"method": "DHL", "number": "123-12345678"}\]**

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### shippingMethod

An object represents the selected shipping method.

The object has two properties, **code** and **label**.

The **code** property is a string contains the shipping method code.

The **label** property is a string contains the shipping method label.

Example of data: **{"code": "ups_3", "label": "UPS"}**

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### shippingCostAmount

The shipping cost for the order.

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### paymentStatus

An object represents the payment status that indicates whether the order is already paid in full, the payment for the order is authorized, etc.

The object has two properties, **code** and **label**.

The **code** property is a string contains the payment status code. Possible values: `full`, `partially`, `invoiced`, `authorized`, `declined`, `pending`.

The **label** property is a string contains the payment status label.

Example of data: **{"code": "pending", "label": "Pending payment"}**

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### paymentMethod

An array of the selected payment methods.

Each element of the array is an object with two properties, **code** and **label**.

The **code** property is a string contains the payment method code.

The **label** property is a string contains the payment method label.

Example of data: **\[{"code": "payment_term_1", "label": "Payment Term"}, {"code": "pay_pal_express_5", "label": "PayPal Express"}\]**

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### paymentTerm

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### currency

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### totalValue

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### subtotalValue

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### billingAddress

#### create

{@inheritdoc}

**Only new order address can be passed.**

**The required field.**

### shippingAddress

#### create

{@inheritdoc}

**Only new order address can be passed.**

**The required field.**

### customer

#### create

{@inheritdoc}

**When feature "Guest Checkout" is enabled and an order is created by a visitor, this field is read-only and a passed value will be ignored.**

### customerUser

#### create

{@inheritdoc}

**When feature "Guest Checkout" is enabled and an order is created by a visitor, this field is required.**

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

### parent

#### get_subresource

Retrieve a record of parent order assigned to a specific order.

#### get_relationship

Retrieve ID of parent order record assigned to a specific order record.

### subOrders

#### get_subresource

Retrieve a record of suborder assigned to a specific order record.

#### get_relationship

Retrieve IDs of suborders records assigned to a specific order record.
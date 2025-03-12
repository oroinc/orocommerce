# Oro\Bundle\CheckoutBundle\Entity\Checkout

## ACTIONS

### get

Retrieve a specific checkout record.

{@inheritdoc}

### get_list

Retrieve a collection of checkout records.

{@inheritdoc}

### create

Create a new checkout record.

The created record is returned in the response.

Follow the [Storefront Checkout API Guide](https://doc.oroinc.com/api/checkout-api/) for more details about the checkout process using the API.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "checkouts",
    "attributes": {
      "poNumber": "PO01"
    },
    "relationships": {
      "lineItems": {
        "data": [
          {
            "type": "checkoutlineitems",
            "id": "line_item_1"
          }
        ]
      },
      "billingAddress": {
        "data": {
          "type": "checkoutaddresses",
          "id": "billing_address"
        }
      },
      "shippingAddress": {
        "data": {
          "type": "checkoutaddresses",
          "id": "shipping_address"
        }
      }
    }
  },
  "included": [
    {
      "type": "checkoutlineitems",
      "id": "line_item_1",
      "attributes": {
        "quantity": 1
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "45"
          }
        },
        "productUnit": {
          "data": {
            "type": "productunits",
            "id": "set"
          }
        }
      }
    },
    {
      "type": "checkoutaddresses",
      "id": "billing_address",
      "relationships": {
        "customerUserAddress": {
          "data": {
            "type": "customeruseraddresses",
            "id": "1"
          }
        }
      }
    },
    {
      "type": "checkoutaddresses",
      "id": "shipping_address",
      "attributes": {
        "label": "Primary address",
        "street": "801 Scenic Hwy",
        "city": "Haines City",
        "postalCode": "33844",
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
            "id": "US-FL"
          }
        }
      }
    }
  ]
}
```
{@/request}

To create a checkout based on an existing shopping list or an existing order use the ``checkout`` sub-resource for these entities, e.g. ``POST /api/shoppinglists/1/checkout``.

### update

Edit a specific checkout record.

The updated record is returned in the response.

Follow the [Storefront Checkout API Guide](https://doc.oroinc.com/api/checkout-api/) for more details about the checkout process using the API.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "checkouts",
    "id": "1",
    "attributes": {
      "customerNotes": "Please, call before delivery"
    }
  }
}
```

Example of updating shipping methods for all line item groups when the shipping type is `line_item_group`:

```JSON
{
  "data": {
    "type": "checkouts",
    "id": "1",
    "relationships": {
      "lineItemGroups": {
        "data": [
          {
            "type": "checkoutlineitemgroups",
            "id": "MS1wcm9kdWN0LmNhdGVnb3J5OjM="
          },
          {
            "type": "checkoutlineitemgroups",
            "id": "MS1wcm9kdWN0LmNhdGVnb3J5OjQ="
          }
        ]
      }
    }
  },
  "included": [
    {
      "meta": {
        "update": true
      },
      "type": "checkoutlineitemgroups",
      "id": "MS1wcm9kdWN0LmNhdGVnb3J5OjM=",
      "attributes": {
        "shippingMethod": "flat_rate_6",
        "shippingMethodType": "primary"
      }
    },
    {
      "meta": {
        "update": true
      },
      "type": "checkoutlineitemgroups",
      "id": "MS1wcm9kdWN0LmNhdGVnb3J5OjQ=",
      "attributes": {
        "shippingMethod": "flat_rate_6",
        "shippingMethodType": "primary"
      }
    }
  ]
}
```

Example of updating shipping methods for all line items when the shipping type is `line_item`:

```JSON
{
  "data": {
    "type": "checkouts",
    "id": "1",
    "relationships": {
      "lineItems": {
        "data": [
          {
            "type": "checkoutlineitems",
            "id": "1"
          },
          {
            "type": "checkoutlineitems",
            "id": "2"
          }
        ]
      }
    }
  },
  "included": [
    {
      "meta": {
        "update": true
      },
      "type": "checkoutlineitems",
      "id": "1",
      "attributes": {
        "shippingMethod": "flat_rate_6",
        "shippingMethodType": "primary"
      }
    },
    {
      "meta": {
        "update": true
      },
      "type": "checkoutlineitems",
      "id": "2",
      "attributes": {
        "shippingMethod": "flat_rate_6",
        "shippingMethodType": "primary"
      }
    }
  ]
}
```
{@/request}

### delete

Delete a specific checkout record.

{@inheritdoc}

### delete_list

Delete a collection of checkout records.

{@inheritdoc}

## FIELDS

### createdAt

#### create, update

The date and time of resource record creation.

**The read-only field. A passed value will be ignored.**

### updatedAt

#### create, update

The date and time of the last update of the resource record.

**The read-only field. A passed value will be ignored.**

### source

The entity from which the checkout was created.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### order

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### totalValue

The total amount includes the sum of the line item costs, shipping and handling costs and is adjusted to subtract any discounts applied to the checkout.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### totals

The totals like the sum of the line item costs, shipping cost, taxes, discounts.

Each subtotal is an object with the following properties:

**subtotalType** is a string that contains a type of the subtotal. Possible values: `subtotal`, `shipping_cost`, `tax` or `discount`.

**description** is a string that contains a description of the subtotal.

**amount** is a money value that contains the amount of the subtotal. The amount can be a negative number, e.g. when it represents a discount.

Example of data: **\[{"subtotalType": "subtotal", "description": "Subtotal", "amount": "500.0000"}, {"subtotalType": "shipping_cost", "description": "Shipping", "amount": "10.0000"}\]**

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### completed

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### paymentInProgress

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### coupons

The coupons allied to the checkout record.

Each item is an object with the following properties:

**couponCode** is a string that represents the coupon code.

**description** is a string that represents the coupon description.

Example of data: **\[{"couponCode": "SALE25", "description": "Seasonal Sale"}\]**

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### shippingType

Indicates where a shipping method should be specified. Possible values: `checkout`, `line_item_group` or `line_item`.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### shippingEstimateAmount

{@inheritdoc}

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### lineItemGroups

The groups of checkout line items.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### availableBillingAddresses

#### get_subresource

Retrieve billing addresses available for a specific checkout record.

### availableShippingAddresses

#### get_subresource

Retrieve shipping addresses available for a specific checkout record.

### availableShippingMethods

#### get_subresource

Retrieve shipping methods available for a specific checkout record.

### availablePaymentMethods

#### get_subresource

Retrieve payment methods available for a specific checkout record.

### billingAddress

#### get_subresource

Retrieve a record of address assigned to a specific checkout record.

#### get_relationship

Retrieve ID of address record assigned to a specific checkout record.

### shippingAddress

#### get_subresource

Retrieve a record of address assigned to a specific checkout record.

#### get_relationship

Retrieve ID of address record assigned to a specific checkout record.

### customer

#### get_subresource

Retrieve a record of customer assigned to a specific checkout record.

#### get_relationship

Retrieve ID of customer record assigned to a specific checkout record.

### customerUser

#### get_subresource

Retrieve a record of customer user assigned to a specific checkout record.

#### get_relationship

Retrieve ID of customer user record assigned to a specific checkout record.

### lineItems

#### get_subresource

Retrieve line item records assigned to a specific checkout record.

#### get_relationship

Retrieve IDs of line item records assigned to a specific checkout record.

#### update_relationship

Replace the list of line item assigned to a specific checkout record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "checkoutlineitems",
      "id": "1"
    }
  ]
}
```
{@/request}

#### add_relationship

Set line item records for a specific checkout record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "checkoutlineitems",
      "id": "1"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove line item records from a specific checkout record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "checkoutlineitems",
      "id": "1"
    }
  ]
}
```
{@/request}

### lineItemGroups

#### get_subresource

Retrieve line item group records to which line items from a specific checkout record belong.

#### get_relationship

Retrieve IDs of line item group records to which line items from a specific checkout record belong.

### order

#### get_subresource

Retrieve order record created during the checkout for a specific checkout record.

#### get_relationship

Retrieve IDs of order record created during the checkout for a specific checkout record.

### source

#### get_subresource

Retrieve an entity from which a specific checkout record was created.

#### get_relationship

Retrieve ID of an entity from which a specific checkout record was created.

### coupons

#### add_subresource

Apply a coupon to a specific checkout record.

{@request:json_api}
Example:

```JSON
{
  "meta": {
    "couponCode": "SALE25"
  }
}
```
{@/request}

#### delete_subresource

Remove a coupon applied to a specific checkout record.

{@request:json_api}
Example:

```JSON
{
  "meta": {
    "couponCode": "SALE25"
  }
}
```
{@/request}

### payment

#### get_subresource

Validate whether a specific checkout record is ready for payment.

{@request:json_api}
Example of a response when the checkout is ready for payment:

```JSON
{
  "meta": {
    "message": "The checkout is ready for payment.",
    "paymentUrl": "https://host/api/checkouts/1/paymentPaymentTerm",
    "errors": []
  }
}
```

Example of a response when the checkout is not ready for payment:

```JSON
{
  "meta": {
    "message": "The checkout is not ready for payment.",
    "paymentUrl": null,
    "errors": [
      {
        "status": "400",
        "title": "not blank constraint",
        "detail": "Shipping method is not selected.",
        "source": {
          "pointer": "/data/attributes/shippingMethod"
        }
      },
      {
        "status": "400",
        "title": "not blank constraint",
        "detail": "Payment method is not selected.",
        "source": {
          "pointer": "/data/attributes/paymentMethod"
        }
      }
    ]
  }
}
```

The ``errors`` collection contains errors in the format described in [JSONLAPI Specification](https://jsonapi.org/format/#error-objects).
{@/request}


# Oro\Bundle\CheckoutBundle\Api\Model\AvailableAddress

## FIELDS

### address

A customer or customer user address.

### group

The group name.

### title

A string representation of a customer or customer user address.


# Oro\Bundle\CheckoutBundle\Api\Model\AvailableShippingMethod

## FIELDS

### label

The shipping method label.

### types

The shipping method types.

Each shipping method type is an object with the following properties:

**id** is a string that represents the shipping method type identifier.

**label** is a string that represents the shipping method type label.

**shippingCost** is a money value that contains the shipping cost for this checkout.

**currency** is a string that contains a currency of the shipping cost.

Example of data: **\[{"id": "primary", "label": "Flat Rate", "shippingCost": "10.0000", "currency": "USD"}\]**


# Oro\Bundle\CheckoutBundle\Api\Model\AvailablePaymentMethod

## FIELDS

### label

The payment method label.

### options

The payment method options.


# Oro\Bundle\CheckoutBundle\Api\Model\CheckoutPaymentResponse

## FIELDS

### message

The message that describes the validation result.

### paymentUrl

The URL of API resource that should be used to complete payment when the checkout is ready for payment.

### errors

The collection of errors when the checkout is not ready for payment.


# Oro\Bundle\CheckoutBundle\Api\Model\ChangeCouponRequest

## FIELDS

### couponCode

The coupon code.

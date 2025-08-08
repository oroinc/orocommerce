# Oro\Bundle\OrderBundle\Entity\Order

## ACTIONS

### get

Retrieve a specific order record.

{@inheritdoc}

### get_list

Retrieve a collection of order records.

{@inheritdoc}

### create

Create a new order record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orders",
    "attributes": {
      "identifier": "FR1012401Z",
      "poNumber": "CV032342USDD",
      "customerNotes": "Please, call before delivery",
      "shipUntil": "2017-08-15",
      "currency": "USD"
    },
    "relationships": {
      "billingAddress": {
        "data": {
          "type": "orderaddresses",
          "id": "billing_address_1"
        }
      },
      "lineItems": {
        "data": [
          {
            "type": "orderlineitems",
            "id": "line_item_1"
          }
        ]
      },
      "customer": {
        "data": {
          "type": "customers",
          "id": "1"
        }
      }
    }
  },
  "included": [
    {
      "type": "orderaddresses",
      "id": "billing_address_1",
      "attributes": {
        "label": "Address 01",
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
      "id": "line_item_1",
      "attributes": {
        "productSku": "4HC51",
        "quantity": 19,     
        "value": 23.55,
        "currency": "USD",
        "priceType": 10,
        "shipBy": "2016-04-30"
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "23"
          }
        },
        "productUnit": {
          "data": {
            "type": "productunits",
            "id": "piece"
          }
        }      
      }
    }
  ]
}
```
{@/request}

### update

Edit a specific order record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orders",
    "id": "1",
    "attributes": {
      "customerNotes": "Please, call before delivery"
    }
  }
}
```
{@/request}

### delete

Delete a specific order record.

{@inheritdoc}

### delete_list

Delete a collection of order records.

{@inheritdoc}

## FIELDS

### customer

#### create

{@inheritdoc}

**The required field.**

### createdBy

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### external

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### lineItems

#### create

{@inheritdoc}

**The required field.**

### source

The entity from which this order was created.

### currency

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### subtotalValue

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### totalValue

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### subtotalWithDiscountsValue

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### totalDiscountsAmount

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### internalStatus

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### status

#### create, update

{@inheritdoc}

**The read-only field when "Enable External Status Management" configuration option is disabled. A passed value will be ignored in this case.**

### orderSubtotals

Calculated order subtotal records for a specific order.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### invoices

Collection of invoice records assigned to a specific order.

## SUBRESOURCES

### billingAddress

#### get_subresource

Retrieve a record of address assigned to a specific order record.

#### get_relationship

Retrieve ID of address record assigned to a specific order record.

#### update_relationship

Replace the address record assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderaddresses",
    "id": "1"
  }
}
```
{@/request}

### customer

#### get_subresource

Retrieve a record of customer assigned to a specific order record.

#### get_relationship

Retrieve ID of customer record assigned to a specific order record.

#### update_relationship

Replace the customer assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customers",
    "id": "1"
  }
}
```
{@/request}

### createdBy

#### get_subresource

Retrieve a record of a back-office user which created the Order.

#### get_relationship

Retrieve ID of a back-office user which created the Order.

### customerUser

#### get_subresource

Retrieve a record of customer user assigned to a specific order record.

#### get_relationship

Retrieve ID of customer user record assigned to a specific order record.

#### update_relationship

Replace customer user assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customerusers",
    "id": "1"
  }
}
```
{@/request}

### discounts

#### get_subresource

Retrieve records of discount assigned to a specific order record.

#### get_relationship

Retrieve IDs of discount records assigned to a specific order record.

#### update_relationship

Replace the list of discount assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderdiscounts",
      "id": "6"
    }
  ]
}
```
{@/request}

#### add_relationship

Set discount records for a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderdiscounts",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove discount records from a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderdiscounts",
      "id": "2"
    }
  ]
}
```
{@/request}

### invoices

#### get_subresource

Retrieve the invoices collection assigned to a specific order record.

#### get_relationship

Retrieve the IDs of the invoices assigned to a specific order record.

#### update_relationship

Replace the list of invoices assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "invoices",
      "id": "1"
    }
  ]
}
```
{@/request}

#### add_relationship

Add invoices to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "invoices",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove invoices from a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "invoices",
      "id": "1"
    }
  ]
}
```
{@/request}

### lineItems

#### get_subresource

Retrieve a record of line item assigned to a specific order record.

#### get_relationship

Retrieve IDs of line item records assigned to a specific order record.

#### update_relationship

Replace the list of line item assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderlineitems",
      "id": "2"
    }
  ]
}
```
{@/request}

#### add_relationship

Set line item records for a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderlineitems",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove line item records from a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderlineitems",
      "id": "1"
    }
  ]
}
```
{@/request}

### organization

#### get_subresource

Retrieve the record of the organization a specific order record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific order record will belong to.

#### update_relationship

Replace the organization a specific order record belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "organizations",
    "id": "1"
  }
}
```
{@/request}

### owner

#### get_subresource

Retrieve the record of the user who is an owner of a specific order record.

#### get_relationship

Retrieve the ID of the user who is an owner of a specific order record.

#### update_relationship

Replace the owner of a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "users",
    "id": "1"
  }
}
```
{@/request}

### shippingAddress

#### get_subresource

Retrieve a record of address assigned to a specific order record.

#### get_relationship

Retrieve ID of address record assigned to a specific order record.

#### update_relationship

Replace the address record assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderaddresses",
    "id": "2"
  }
}
```
{@/request}

### shippingTrackings

#### get_subresource

Retrieve a record of shipping tracking assigned to a specific order record.

#### get_relationship

Retrieve IDs of shipping tracking records assigned to a specific order record.

#### update_relationship

Replace the list of shipping tracking assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "ordershippingtrackings",
      "id": "3"
    }
  ]
}
```
{@/request}

#### add_relationship

Set shipping tracking records for a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "ordershippingtrackings",
      "id": "3"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove shipping tracking records from a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "ordershippingtrackings",
      "id": "3"
    }
  ]
}
```
{@/request}

### paymentTerm

#### get_subresource

Retrieve a record of payment term assigned to a specific order record.

#### get_relationship

Retrieve ID of payment term record assigned to a specific order record.

#### update_relationship

Replace the payment term assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "paymentterms",
    "id": "2"
  }
}
```
{@/request}

### source

#### get_subresource

Retrieve the entity from which a specific order was created.

#### get_relationship

Retrieve the ID the entity from which a specific order was created.

#### update_relationship

Retrieve the entity from which a specific order was created.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "shoppinglists",
    "id": "1"
  }
}
```
{@/request}

### parent

#### get_subresource

Retrieve a record of parent order assigned to a specific order record.

#### get_relationship

Retrieve ID of parent order record assigned to a specific order record.

#### update_relationship

Replace the parent order assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orders",
    "id": "2"
  }
}
```
{@/request}

### subOrders

#### get_subresource

Retrieve a record of suborders assigned to a specific order record.

#### get_relationship

Retrieve IDs of suborders records assigned to a specific order record.

#### update_relationship

Replace the list of suborders assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orders",
      "id": "2"
    }
  ]
}
```
{@/request}

#### add_relationship

Set suborders records for a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orders",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove suborders records from a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orders",
      "id": "1"
    }
  ]
}
```
{@/request}

### internalStatus

#### get_subresource

Retrieve internal status record assigned to a specific order record.

#### get_relationship

Retrieve the ID of the internal status record assigned to a specific order record.

### status

#### get_subresource

Retrieve status record assigned to a specific order record.

#### get_relationship

Retrieve the ID of the status record assigned to a specific order record.

#### update_relationship

Replace the status record assigned to a specific order record.

**Note:**
The status can be changed only when "Enable External Status Management" configuration option is enabled.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderstatuses",
    "id": "open"
  }
}
```
{@/request}

### shippingStatus

#### get_subresource

Retrieve shipping status record assigned to a specific order record.

#### get_relationship

Retrieve the ID of the shipping status record assigned to a specific order record.

#### update_relationship

Replace the shipping status record assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "ordershippingstatuses",
    "id": "shipped"
  }
}
```
{@/request}


# Extend\Entity\EV_Order_Internal_Status

## ACTIONS

### get

Retrieve a specific order internal status record.

### get_list

Retrieve a collection of order internal status records.


# Extend\Entity\EV_Order_Status

## ACTIONS

### get

Retrieve a specific order status record.

### get_list

Retrieve a collection of order status records.


# Extend\Entity\EV_Order_Shipping_Status

## ACTIONS

### get

Retrieve a specific order shipping status record.

### get_list

Retrieve a collection of order shipping status records.

# Oro\Bundle\OrderBundle\Entity\OrderLineItem

## ACTIONS

### get

Retrieve a specific order line item record.

{@inheritdoc}

### get_list

Retrieve a collection of order line item records.

{@inheritdoc}

### create

Create a new order line item record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderlineitems",
    "attributes": {
      "productSku": "4HC51",
      "quantity": 19,     
      "value": 23.55,
      "currency": "USD",
      "priceType": 10,
      "shipBy": "2016-04-30"
    },
    "relationships": {
      "orders": {
        "data": [
          {
            "type": "orders",
            "id": "1"
          }
        ]
      },
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
}
```
{@/request}

### update

Edit a specific order line item record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderlineitems",
    "id": "1",
    "attributes": {
      "quantity": 19,     
      "value": 23.55,      
      "priceType": 10,
      "shipBy": "2016-04-30"
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "23"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific order line item record.

{@inheritdoc}

### delete_list

Delete a collection of line item records.

{@inheritdoc}

## FIELDS

### orders

#### create

{@inheritdoc}

**The required field.**

### productUnit

#### create

{@inheritdoc}

**The required field.**

### productSku

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### quantity

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### value

#### create

{@inheritdoc}

**The required field.**

**In the case of products with type `kit` this is a read-only field. A passed value will be ignored.
The calculated price is the result of the following: a product kit product price taken from a price list + kit item line items' prices taken from the request body.

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

**In the case of products with type `kit` this is a read-only field. A passed value will be ignored.
The calculated price is the result of the following: a product kit product price taken from a price list + kit item line items' prices taken from the request body.

### currency

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### productUnitCode

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### checksum

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### orders

#### get_subresource

Retrieve the order records a specific line item record is assigned to.

#### get_relationship

Retrieve the IDs of the order records which a specific line item record is assigned to.

### parentProduct

#### get_subresource

Retrieve a record of parent product assigned to a specific line item record.

#### get_relationship

Retrieve the ID of parent product record assigned to a specific line item record.

#### update_relationship

Replace the parent product assigned to a specific line item record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "1"
  }
}
```
{@/request}

### product

#### get_subresource

Retrieve a record of product assigned to a specific line item record.

#### get_relationship

Retrieve the ID of product record assigned to a specific line item record.

#### update_relationship

Replace the product assigned to a specific line item record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "1"
  }
}
```
{@/request}

### productUnit

#### get_subresource

Retrieve a record of product unit assigned to a specific line item record.

#### get_relationship

Retrieve the ID of product unit record assigned to a specific line item record.

#### update_relationship

Replace the product unit assigned to a specific line item record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunits",
    "id": "item"
  }
}
```
{@/request}

### kitItemLineItems

#### get_subresource

Retrieve a list of order product kit item line item records assigned to a specific line item record.

#### get_relationship

Retrieve IDs of order product kit item line item records assigned to a specific line item record.

#### update_relationship

Replace the list of order product kit item line items assigned to a specific line item record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderproductkititemlineitems",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove order product kit item line item records from a specific line item record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderproductkititemlineitems",
      "id": "1"
    }
  ]
}
```
{@/request}

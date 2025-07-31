# Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem

## ACTIONS

### get

Retrieve a specific checkout product kit item line item record.

{@inheritdoc}

### get_list

Retrieve a collection of checkout product kit item line item records.

{@inheritdoc}

### create

Create a new checkout product kit item line item record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "checkoutproductkititemlineitems",
    "attributes": {
      "quantity": 3,
      "sortOrder": 1
    },
    "relationships": {
      "lineItem": {
        "data": {
          "type": "checkoutlineitems",
          "id": "1"
        }
      },
      "kitItem": {
        "data": {
          "type": "productkititems",
          "id": "2"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "3"
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
}
```
{@/request}

### update

Edit a specific checkout product kit item line item record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "checkoutproductkititemlineitems",
    "id": "1",
    "attributes": {
      "quantity": 3
    },
    "relationships": {
      "productUnit": {
        "data": {
          "type": "productunits",
          "id": "item"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific checkout product kit item line item record.

{@inheritdoc}

### delete_list

Delete a collection of checkout product kit item line item records.

{@inheritdoc}

## FIELDS

### price

Price for a product kit item line item product unit.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### currency

A currency for a product kit item line item price.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### subTotal

The product price multiplied by the quantity.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productUnit

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

### product

#### create, update

{@inheritdoc}

**Note:**
This value can be omitted if the **productSku** field is specified in the request.

## SUBRESOURCES

### kitItem

#### get_subresource

Retrieve a record of kit item assigned to a specific checkout product kit item line item record.

#### get_relationship

Retrieve the ID of kit item record assigned to a specific checkout product kit item line item record.

### lineItem

#### get_subresource

Retrieve a record of line item assigned to a specific checkout product kit item line item record.

#### get_relationship

Retrieve the ID of line item record assigned to a specific checkout product kit item line item record.

### product

#### get_subresource

Retrieve a record of product assigned to a specific checkout product kit item line item record.

#### get_relationship

Retrieve the ID of product record assigned to a specific checkout product kit item line item record.

### productUnit

#### get_subresource

Retrieve a record of product unit assigned to a specific checkout product kit item line item record.

#### get_relationship

Retrieve the ID of product unit record assigned to a specific checkout product kit item line item record.

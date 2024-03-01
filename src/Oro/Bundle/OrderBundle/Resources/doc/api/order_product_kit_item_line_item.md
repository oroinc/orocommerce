# Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem

## ACTIONS

### get

Retrieve a specific order product kit item line item record.

{@inheritdoc}

### get_list

Retrieve a collection of order product kit item line item records.

{@inheritdoc}

### create

Create a new order product kit item line item record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderproductkititemlineitems",
    "attributes": {
      "kitItemLabel": "Base Unit",
      "optional": false,
      "productSku": "2JD29",
      "productName": "Handheld Flashlight",
      "quantity": 1,
      "productUnitCode": "item",
      "sortOrder": 1,
      "value": "13.5900",
      "currency": "USD"
    },
    "relationships": {
      "lineItem": {
        "data": {
          "type": "orderlineitems",
          "id": "178"
        }
      },
      "kitItem": {
        "data": {
          "type": "productkititems",
          "id": "5"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "7"
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

Edit a specific order product kit item line item record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderproductkititemlineitems",
    "id": "2",
    "attributes": {
      "quantity": 2,
      "productUnitCode": "item",
      "sortOrder": 2,
      "value": "14.5900",
      "currency": "USD"
    },
    "relationships": {
      "kitItem": {
        "data": {
          "type": "productkititems",
          "id": "4"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "6"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific order product kit item line item record.

{@inheritdoc}

### delete_list

Delete a collection of line item records.

{@inheritdoc}

## FIELDS

### lineItem

#### create

{@inheritdoc}

**The required field.**

### kitItem

#### create

{@inheritdoc}

**The required field.**

### kitItemId

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### kitItemLabel

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### optional

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### minimumQuantity

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### maximumQuantity

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productId

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productSku

#### create, update

{@inheritdoc}

**Will be used to set a Product if it was not submitted. Otherwise, will be ignored.**

### productName

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### quantity

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### productUnit

#### create

{@inheritdoc}

**The required field.**

### productUnitCode

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productUnitPrecision

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### sortOrder

#### create, update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### value

#### create

{@inheritdoc}

**The required field.**

**If this field changes then will be changed related price value field of `orderlineitems`.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

**If the value of the field changes then will be changed related price value of `orderlineitems`.**

### currency

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

## SUBRESOURCES

### kitItem

#### get_subresource

Retrieve the related kit item.

#### get_relationship

Retrieve the ID of the related kit item.

### lineItem

#### get_subresource

Retrieve the related line item.

#### get_relationship

Retrieve the ID of the related line item.

### product

#### get_subresource

Retrieve a record of product assigned to a specific order product kit item line item record.

#### get_relationship

Retrieve ID of product record assigned to a specific order product kit item line item record.

#### update_relationship

Replace the product assigned to a specific order product kit item line item record.

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

Retrieve a record of product unit assigned to a specific order product kit item line item record.

#### get_relationship

Retrieve ID of product unit record assigned to a specific order product kit item line item record.

#### update_relationship

Replace the product unit assigned to a specific order product kit item line item record.

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

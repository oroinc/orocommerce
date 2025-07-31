# Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem

## ACTIONS

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### create

Create a new shopping list product kit item line item record.

The created record is returned in the response.

{@inheritdoc}

**Note:** When an item is added to the default shopping list by using the string `default` as the shopping list ID
and there is no a shopping list, it will be created automatically.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "shoppinglistkititems",
    "attributes": {
      "quantity": 3,
      "sortOrder": 1
    },
    "relationships": {
      "lineItem": {
        "data": {
          "type": "shoppinglistitems",
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
      "unit": {
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

Edit a specific shopping list product kit item line item record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "shoppinglistkititems",
    "id": "1",
    "attributes": {
      "quantity": 3
    },
    "relationships": {
      "unit": {
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

Delete a specific shopping list product kit item line item record.

{@inheritdoc}

### delete_list

Delete a collection of shopping list product kit item line item records.

{@inheritdoc}

## FIELDS

### lineItem

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### kitItem

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### product

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

### unit

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### sortOrder

{@inheritdoc}

### value

The product price.

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### currency

The currency for the product price.

#### create

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### subTotal

The product price multiplied by the quantity.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

# Oro\Bundle\ShoppingListBundle\Entity\LineItem

## ACTIONS

### get

Retrieve a specific shopping list item record.

{@inheritdoc}

### get_list

Retrieve a collection of shopping list item records.

{@inheritdoc}

### create

Create a new shopping list item record.

The created record is returned in the response.

{@inheritdoc}

**Note:** When an item is added to the default shopping list by using the string `default` as the shopping list ID
and there is no a shopping list, it will be created automatically.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "shoppinglistitems",
    "attributes": {
      "quantity": 10
    },
    "relationships": {
      "shoppingList": {
        "data": {
          "type": "shoppinglists",
          "id": "1"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "23"
        }
      },
      "unit": {
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

Edit a specific shopping list item record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "shoppinglistitems",
    "id": "1",
    "attributes": {
      "quantity": 10
    },
    "relationships": {
      "unit": {
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

### delete

Delete a specific shopping list item record.

{@inheritdoc}

### delete_list

Delete a collection of shopping list item records.

{@inheritdoc}

## FIELDS

### product

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### quantity

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### unit

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### kitItems

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### shoppingList

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### value

The product price.

**The read-only field. A passed value will be ignored.**

### currency

The currency for the product price.

**The read-only field. A passed value will be ignored.**

# Oro\Bundle\ShoppingListBundle\Entity\LineItem

## ACTIONS

### get

Retrieve a specific shopping list line item record.

{@inheritdoc}

### get_list

Retrieve a collection of shopping list line item records.

{@inheritdoc}

### create

Create a new shopping list line item record.

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

Edit a specific shopping list line item record.

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

Delete a specific shopping list line item record.

{@inheritdoc}

### delete_list

Delete a collection of shopping list line item records.

{@inheritdoc}

## FIELDS

### product

#### create, add_shopping_list_items

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### quantity

#### create, add_shopping_list_items

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### unit

#### create, add_shopping_list_items

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### kitItems

#### create, add_shopping_list_items

{@inheritdoc}

**The required field if the base product is a kit.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed and the base product is a kit.**

### shoppingList

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

#### add_shopping_list_items

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### value

The product price.

#### create, add_shopping_list_items

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### currency

The currency for the product price.

#### create, add_shopping_list_items

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### checksum

#### create, update, add_shopping_list_items

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

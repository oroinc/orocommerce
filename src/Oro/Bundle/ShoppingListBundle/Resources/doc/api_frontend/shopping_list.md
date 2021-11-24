# Oro\Bundle\ShoppingListBundle\Entity\ShoppingList

## ACTIONS

### get

Retrieve a specific shopping list record.

{@inheritdoc}

### get_list

Retrieve a collection of shopping list records.

{@inheritdoc}

### create

Create a new shopping list record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "shoppinglists",
    "attributes": {
      "name": "First Shopping List"
    }
  }
}
```
{@/request}

### update

Edit a specific shopping list record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "shoppinglists",
    "id": "1",
    "attributes": {
      "notes": "Please, call before delivery"
    }
  }
}
```
{@/request}

### delete

Delete a specific shopping list record.

{@inheritdoc}

### delete_list

Delete a collection of shopping list records.

{@inheritdoc}

## FIELDS

### name

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### default

Indicates whether this shopping list is a default one or not for the current logged in user.

### total

The total amount that is due for payment of the items in the shopping list.

### subTotal

The total cost of the items in the shopping list.

### currency

The currency for the shopping list aggregated amounts, such as total and subTotal.

### customer

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### customerUser

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### items

#### get_subresource

Retrieve item records assigned to a specific shopping list record.

#### get_relationship

Retrieve IDs of item records assigned to a specific shopping list record.

#### add_subresource

Add an item or the list of items to a specific shopping list.

In case an item already exists in the shopping list, the quantity of the product will be increased,
the result quantity will be the sum of the existing quantity and the quantity specified in the request.

**Note:** When an item is added to the default shopping list by using the string `default` as the shopping list ID
and there is no a shopping list, it will be created automatically.

The all added and updated records are returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "shoppinglistitems",
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
        "unit": {
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

### customer

#### get_subresource

Retrieve a record of customer assigned to a specific shopping list record.

#### get_relationship

Retrieve ID of customer record assigned to a shopping list record.

### customerUser

#### get_subresource

Retrieve a record of customer user assigned to a specific shopping list record.

#### get_relationship

Retrieve ID of customer user record assigned to a shopping list record.

# Oro\Bundle\InventoryBundle\Entity\InventoryLevel

## ACTIONS

### get

{@inheritdoc}

### get_list

{@inheritdoc}

### update

Updates a single **Inventory Level**.

This API can be used to update the Inventory Level quantity.

{@request:json_api}
Example:

`</admin/api/products/12>`

```JSON
{
  "data": {
    "type": "inventorylevels",
    "id": "1",
    "attributes": {
      "quantity": "222"
    }
   }
}
```
{@/request}

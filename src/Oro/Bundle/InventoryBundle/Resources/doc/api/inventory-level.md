# Oro\Bundle\InventoryBundle\Entity\InventoryLevel

## ACTIONS

### get

Retrieve a specific inventory level record.

{@inheritdoc}

### get_list

Retrieve a collection of inventory level records.

The list of records to be returned can be limited by filters.

{@inheritdoc}

### update

Edit a specific inventory level record.

The updated record is returned in the response body.

{@inheritdoc}

{@request:json_api}
Example:

`</admin/api/inventorylevels/1>`

```JSON
{
  "data": {
    "type": "inventorylevels",
    "id": "1",
    "attributes": {
      "quantity": "51.0000000000"
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      },
      "productUnitPrecision": {
        "data": {
          "type": "productunitprecisions",
          "id": "1"
        }
      },
      "warehouse": {
        "data": {
          "type": "warehouses",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

## FIELDS

### quantity

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

## SUBRESOURCES

### product

#### get_subresource

Retrieve a record of product assigned to a specific inventory level record.

#### get_relationship

Retrieve the ID of the product record assigned to a specific inventory level record.

#### update_relationship

Replace the product assigned to a specific inventory level record.

{@request:json_api}
Example:

`</admin/api/inventorylevels/1/relationships/product>`

```JSON
{
  "data": {
    "type": "products",
    "id": "1"
  }
}
```
{@/request}

### productUnitPrecision

#### get_subresource

Retrieve a record of the product unit precision assigned to a specific inventory level record.

#### get_relationship

Retrieve the ID of the product unit precision record assigned to a specific inventory level record.

#### update_relationship

Replace the product unit precision assigned to a specific inventory level record.

{@request:json_api}
Example:

`</admin/api/inventorylevels/1/relationships/productUnitPrecision>`

```JSON
{
  "data": {
    "type": "productunitprecisions",
    "id": "1"
  }
}
```
{@/request}

### warehouse

#### get_subresource

Retrieve a record of the warehouse assigned to a specific inventory level record.

#### get_relationship

Retrieve the ID of the warehouse record assigned to a specific inventory level record.

#### update_relationship

Replace the warehouse assigned to a specific inventory level record.

{@request:json_api}
Example:

`</admin/api/inventorylevels/1/relationships/warehouse>`

```JSON
{
  "data": {
    "type": "warehouses",
    "id": "1"
  }
}
```
{@/request}

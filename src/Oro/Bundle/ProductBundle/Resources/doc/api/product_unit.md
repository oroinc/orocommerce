# Oro\Bundle\ProductBundle\Entity\ProductUnit

## ACTIONS

### get

Retrieve a specific product unit record.

{@inheritdoc}

### get_list

Retrieve a collection of product unit records.

The list of records that will be returned, could be limited by filters.

{@inheritdoc}

### create

Create a new product unit record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

`</admin/api/productunits>`

```JSON
{
  "data": {
    "type": "productunits",
    "id": "item",
    "attributes": {
      "defaultPrecision": 0
    }
  }
}
```
{@/request}

### update

Edit a specific product unit record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

`</admin/api/productunits/items>`

```JSON
{
  "data": {
    "type": "productunits",
    "id": "item",
    "attributes": {
      "defaultPrecision": 10
    }
  }
}
```
{@/request}

### delete

Delete a specific product unit record.

{@inheritdoc}

### delete_list

Delete a collection of product unit records.

The list of records that will be deleted, could be limited by filters.

{@inheritdoc}

## FIELDS

### defaultPrecision

#### create

{@inheritdoc}

**The required field**

### id

#### create

{@inheritdoc}

**The required field**

# Oro\Bundle\InventoryBundle\Entity\InventoryLevel

## ACTIONS

### get

Retrieve a specific inventory level record.

{@inheritdoc}

### get_list

Retrieve a collection of inventory level records.

{@inheritdoc}

### update

Edit a specific inventory level record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "inventorylevels",
    "id": "1",
    "attributes": {
      "quantity": "51"
    }
  }
}
```
{@/request}

## FIELDS

### quantity

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### product

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productUnitPrecision

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### organization

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### product

#### get_subresource

Retrieve a record of product assigned to a specific inventory level record.

#### get_relationship

Retrieve the ID of the product record assigned to a specific inventory level record.

### productUnitPrecision

#### get_subresource

Retrieve a record of the product unit precision assigned to a specific inventory level record.

#### get_relationship

Retrieve the ID of the product unit precision record assigned to a specific inventory level record.

### organization

#### get_subresource

Retrieve the record of the organization a specific inventory level belongs to.

#### get_relationship

Retrieve the ID of the organization that a specific inventory level belongs to.

# Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct

## ACTIONS

### get

{@inheritdoc}

Retrieve a specific kit item product record.

### get_list

{@inheritdoc}

Retrieve a collection of kit item product records.

### create

{@inheritdoc}

Kit item product can only be created together with a product kit item via the product kit item creation API resource.

### update

{@inheritdoc}

Edit a specific kit item product record.

The updated record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productkititemproducts",
    "id": "1",
    "meta": {
      "update": true
    },
    "attributes": {
      "sortOrder": 2
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "simple-product-id"
        }
      }
    }
  }
}
```
 {@/request}

## FIELDS

### kitItem

{@inheritdoc}

#### create

**The required field.**

#### update

**The read-only field. A passed value will be ignored.**

### sortOrder

{@inheritdoc}

### product

{@inheritdoc}

Only the products of type "simple" are allowed to be referenced.

#### create

**The required field.**

### productUnitPrecision

{@inheritdoc}

This field is populated automatically based on the product unit of the related product kit item. 

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### kitItem

#### get_subresource

Retrieve the related kit item.

#### get_relationship

Retrieve the ID of the related kit item.

### product

#### get_subresource

Retrieve the related product.

#### get_relationship

Retrieve the ID of the related product.

#### update_relationship

Replace the product for the specified kit item product.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "simple-product-id"
  }
}
```
{@/request}

### productUnitPrecision

#### get_subresource

Retrieve the related product unit precision.

#### get_relationship

Retrieve the ID of the related product unit precision.

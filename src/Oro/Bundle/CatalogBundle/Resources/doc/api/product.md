# Oro\Bundle\ProductBundle\Entity\Product

## FIELDS

### category

The master catalog category.

### category_sort_order

The sort order of the product in the master catalog category.

## SUBRESOURCES

### category

#### get_subresource

Retrieve a record of the master catalog category that a specific product belongs to.

#### get_relationship

Retrieve the ID of the master catalog category that a specific product belongs to.

#### update_relationship

Move a specific product to another master catalog category.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "categories",
    "id": "20"
  }
}
```
{@/request}

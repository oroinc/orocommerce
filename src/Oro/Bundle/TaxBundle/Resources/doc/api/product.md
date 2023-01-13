# Oro\Bundle\ProductBundle\Entity\Product

## SUBRESOURCES

### taxCode

#### get_subresource

Retrieve the record for the tax code of a specific product record.

#### get_relationship

Retrieve the ID of the tax code of a specific product record.

#### update_relationship

Replace the tax code for a specific product.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "producttaxcodes",
    "id": "1"
  }
}
```
{@/request}

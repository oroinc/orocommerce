# Oro\Bundle\OrderBundle\Entity\OrderLineItem

## SUBRESOURCES

### freeFormTaxCode

#### get_subresource

Retrieve the record for the tax code of a specific order line item record.

#### get_relationship

Retrieve the ID of the tax code of a specific order line item record.

#### update_relationship

Replace the tax code for a specific order line item record.

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

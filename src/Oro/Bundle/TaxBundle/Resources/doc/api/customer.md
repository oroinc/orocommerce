# Oro\Bundle\CustomerBundle\Entity\Customer

## SUBRESOURCES

### taxCode

#### get_subresource

Retrieve the record for the tax code of a specific customer record.

#### get_relationship

Retrieve the ID of the tax code of a specific customer record.

#### update_relationship

Replace the tax code for a specific customer.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customertaxcodes",
    "id": "1"
  }
}
```
{@/request}


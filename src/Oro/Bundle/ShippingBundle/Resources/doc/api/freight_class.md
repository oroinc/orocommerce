# Oro\Bundle\ShippingBundle\Entity\FreightClass

## ACTIONS

### get

Retrieve a specific freight class record.

{@inheritdoc}

### get_list

Retrieve a collection of freight class records.

{@inheritdoc}

### create

Create a new freight class record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "freightclasses",
    "id": "bigbeg"
  }
}
```
{@/request}

### delete

Delete a specific length unit record.

{@inheritdoc}

### delete_list

Delete a collection of length unit records.

{@inheritdoc}

# Oro\Bundle\ShippingBundle\Entity\LengthUnit

## ACTIONS

### get

Retrieve a specific length unit record.

{@inheritdoc}

### get_list

Retrieve a collection of length unit records.

{@inheritdoc}

### create

Create a new length unit record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "lengthunits",
    "id": "cm",
    "attributes": {
      "conversionRates": {
        "inch": 0.393701,
        "foot": 0.0328084,
        "m": 0.01
      }
    }
  }
}
```
{@/request}

### update

Edit a specific weight unit record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "lengthunits",
    "id": "cm",
    "attributes": {
      "conversionRates": {
        "inch": 0.393701,
        "foot": 0.0328084,
        "m": 0.01,
        "mm": 10
      }
    }
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

## FIELDS

### conversionRates

Conversion rates.

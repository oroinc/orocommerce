# Oro\Bundle\ShippingBundle\Entity\WeightUnit

## ACTIONS

### get

Retrieve a specific weight unit record.

{@inheritdoc}

### get_list

Retrieve a collection of weight unit records.

{@inheritdoc}

### create

Create a new weight unit record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "weightunits",
    "id": "g",
    "attributes": {
      "conversionRates": {
        "lbs": 0.00220462262
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
    "type": "weightunits",
    "id": "g",
    "attributes": {
      "conversionRates": {
        "lbs": 0.00220462262,
        "kg": 0.01
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

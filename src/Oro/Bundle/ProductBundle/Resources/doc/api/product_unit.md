# Oro\Bundle\ProductBundle\Entity\ProductUnit

## ACTIONS

### get

Retrieve a specific product unit record.

{@inheritdoc}

### get_list

Retrieve a collection of product unit records.

{@inheritdoc}

### create

Create a new product unit record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunits",
    "id": "kilogram",
    "attributes": {
      "defaultPrecision": 0,
      "label": "kilogram",
      "shortLabel": "kg",
      "pluralLabel": "kilograms",
      "shortPluralLabel": "kgs"
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

```JSON
{
  "data": {
    "type": "productunits",
    "id": "kilogram",
    "attributes": {
      "defaultPrecision": 10,
      "label": "kilogram"
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

{@inheritdoc}

## FIELDS

### label

The localized label of the product unit.

#### create

{@inheritdoc}

**The required field.**

### shortLabel

The localized short label of the product unit.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### pluralLabel

The localized plural label of the product unit.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### shortPluralLabel

The localized short plural label of the product unit.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### defaultPrecision

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

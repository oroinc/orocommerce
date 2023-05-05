# Oro\Bundle\TaxBundle\Entity\Tax

## ACTIONS

### get

Retrieve a specific tax record.

{@inheritdoc}

### get_list

Retrieve a collection of tax records.

{@inheritdoc}

### create

Create a new tax record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "taxes",
    "attributes": {
      "code": "SOME_TAX",
      "description": "Some tax description",
      "rate": 0.02
    }
  }
}
```
{@/request}

### update

Edit a specific tax record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "taxes",
    "id": "1",
    "attributes": {
      "code": "SOME_TAX",
      "description": "Some tax description",
      "rate": 0.02
    }
  }
}
```
{@/request}

### delete

Delete a specific tax record.

{@inheritdoc}

### delete_list

Delete a collection of tax records.

{@inheritdoc}

## FIELDS

### code

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### rate

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

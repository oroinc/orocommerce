# Oro\Bundle\TaxBundle\Entity\ProductTaxCode

## ACTIONS

### get

Retrieve a specific product tax code record.

{@inheritdoc}

### get_list

Retrieve a collection of product tax code records.

{@inheritdoc}

### create

Create a new product tax code record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "producttaxcodes",
    "attributes": {
      "code": "SOME_TAX_CODE",
      "description": "Some tax code description"
    }
  }
}
```
{@/request}

### update

Edit a specific product tax code record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "producttaxcodes",
    "id": "1",
    "attributes": {
      "code": "SOME_TAX_CODE",
      "description": "Some tax code description"
    }
  }
}
```
{@/request}

### delete

Delete a specific product tax code record.

{@inheritdoc}

### delete_list

Delete a collection of product tax code records.

{@inheritdoc}

## FIELDS

### code

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### organization

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### organization

#### get_subresource

Retrieve the record of the organization a specific product tax code record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific product tax code record belongs to.

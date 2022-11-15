# Oro\Bundle\TaxBundle\Entity\TaxRule

## ACTIONS

### get

Retrieve a specific tax rule record.

{@inheritdoc}

### get_list

Retrieve a collection of tax rule records.

{@inheritdoc}

### create

Create a new tax rule record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "taxrules",
    "attributes": {
      "description": "Los Angeles taxes"
    },
    "relationships": {
      "customerTaxCode": {
        "data": {
          "type": "customertaxcodes",
          "id": "1"
        }
      },
      "productTaxCode": {
        "data": {
          "type": "producttaxcodes",
          "id": "1"
        }
      },
      "tax": {
        "data": {
          "type": "taxes",
          "id": "1"
        }
      },
      "taxJurisdiction": {
        "data": {
          "type": "taxjurisdictions",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific tax rule record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "taxrules",
    "id": "1",
    "attributes": {
      "description": "Tax rule description"
    }
  }
}
```
{@/request}

### delete

Delete a specific tax rule record.

{@inheritdoc}

### delete_list

Delete a collection of tax rule records.

{@inheritdoc}

## FIELDS

### customerTaxCode

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### productTaxCode

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### tax

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### taxJurisdiction

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

### customerTaxCode

#### get_subresource

Retrieve the record of the customer tax code that a specific tax rule record is associated with.

#### get_relationship

Retrieve the ID of the customer tax code record which a specific tax rule record is associated with.

#### update_relationship

Replace the customer tax code associated with a specific tax rule record.

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

### productTaxCode

#### get_subresource

Retrieve the record of the product tax code that a specific tax rule record is associated with.

#### get_relationship

Retrieve the ID of the product tax code record which a specific tax rule record is associated with.

#### update_relationship

Replace the product tax code associated with a specific tax rule record.

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

### tax

#### get_subresource

Retrieve the record of the tax that a specific tax rule record is associated with.

#### get_relationship

Retrieve the ID of the tax record that a specific tax rule record is associated with.

#### update_relationship

Replace the tax associated with a specific tax rule record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "taxes",
    "id": "1"
  }
}
```
{@/request}

### taxJurisdiction

#### get_subresource

Retrieve the record of the tax jurisdiction that a specific tax rule record is associated with.

#### get_relationship

Retrieve the ID of the tax jurisdiction record that a specific tax rule record is associated with.

#### update_relationship

Replace the tax jurisdiction associated with a specific tax rule record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "taxjurisdictions",
    "id": "1"
  }
}
```
{@/request}

### organization

#### get_subresource

Retrieve the record of the organization a specific tax rule record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific tax rule record belongs to.

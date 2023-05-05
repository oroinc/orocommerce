# Oro\Bundle\TaxBundle\Entity\CustomerTaxCode

## ACTIONS

### get

Retrieve a specific customer tax code record.

{@inheritdoc}

### get_list

Retrieve a collection of customer tax code records.

{@inheritdoc}

### create

Create a new customer tax code record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customertaxcodes",
    "attributes": {
      "code": "SOME_TAX_CODE",
      "description": "Some tax code description"
    }
  }
}
```
{@/request}

### update

Edit a specific customer tax code record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customertaxcodes",
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

Delete a specific customer tax code record.

{@inheritdoc}

### delete_list

Delete a collection of customer tax code records.

{@inheritdoc}

## FIELDS

### code

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### owner

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### organization

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### owner

#### get_subresource

Retrieve the record of the user a specific customer tax code record belongs to.

#### get_relationship

Retrieve the ID of the user record which a specific customer tax code record belongs to.

### organization

#### get_subresource

Retrieve the record of the organization a specific customer tax code record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific customer tax code record belongs to.

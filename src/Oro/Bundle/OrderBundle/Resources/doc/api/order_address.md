# Oro\Bundle\OrderBundle\Entity\OrderAddress

## ACTIONS

### get

Retrieve a specific order address record.

{@inheritdoc}

### get_list

Retrieve a collection of order address records.

{@inheritdoc}

### create

Create a new order address record.

The created record is returned in the response.

{@inheritdoc}

**Note:**
It is sufficient for the submitted address to have at least one of the following:
a customer user address, or a customer address, or filled in address fields.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderaddresses",
    "attributes": {
      "phone": "1234567890",
      "label": "Address 01",
      "street": "1215 Caldwell Road",
      "city": "Rochester",
      "postalCode": "14608",
      "firstName": "Amanda",
      "lastName": "Cole"
    },
    "relationships": {
      "country": {
        "data": {
          "type": "countries",
          "id": "US"
        }
      },
      "region": {
        "data": {
          "type": "regions",
          "id": "US-NY"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific order address record.

The updated record is returned in the response.

{@inheritdoc}

**Note:**
When the submitted data have a customer user address or a customer address,
the address fields will be filled in based on the passed address.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderaddresses",
    "id": "1",
    "attributes": {
      "phone": "1234567890",
      "label": "Address 01",
      "street": "1215 Caldwell Road",
      "city": "Rochester",
      "postalCode": "14608",
      "firstName": "Amanda",
      "lastName": "Cole"
    },
    "relationships": {
      "country": {
        "data": {
          "type": "countries",
          "id": "US"
        }
      },
      "region": {
        "data": {
          "type": "regions",
          "id": "US-NY"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific order address record.

{@inheritdoc}

### delete_list

Delete a collection of order address records.

{@inheritdoc}

## FIELDS

### city

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### postalCode

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### street

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### firstName

#### create

{@inheritdoc}

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must be defined.

#### update

{@inheritdoc}

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must remain defined.

### lastName

#### create

{@inheritdoc}

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must be defined.

#### update

{@inheritdoc}

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must remain defined.

### organization

#### create

{@inheritdoc}

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must be defined.

#### update

{@inheritdoc}

**Conditionally required field:**
Either **organization** or **firstName** and **lastName** must remain defined.

### country

#### create

{@inheritdoc}

**The required field.**

### region

#### create, update

{@inheritdoc}

**Conditionally required field:**
A state is required for some countries.

### customerAddress

#### create, update

{@inheritdoc}

**If specified, data from this address will be copied to the quote shipping address.**

**This field can be passed only if other address fields are empty.**

### customerUserAddress

#### create, update

{@inheritdoc}

**If specified, data from this address will be copied to the quote shipping address.**

**This field can be passed only if other address fields are empty.**

## SUBRESOURCES

### country

#### get_subresource

Retrieve a record of country assigned to a specific address record.

#### get_relationship

Retrieve ID of country record assigned to a specific address record.

#### update_relationship

Replace country assigned to a specific address record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "countries",
    "id": "US"
  }
}
```
{@/request}

### region

#### get_subresource

Retrieve a record of region assigned to a specific region record.

#### get_relationship

Retrieve IDs of region records assigned to a specific region record.

#### update_relationship

Replace region assigned to a specific region record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "regions",
    "id": "US-NY"
  }
}
```
{@/request}

### customerAddress

#### get_subresource

Retrieve a record of customer address which was used to fill in a specific order address record.

#### get_relationship

Retrieve the ID of customer address record which was used to fill in a specific order address record.

### customerUserAddress

#### get_subresource

Retrieve a record of customer user address which was used to fill in a specific order address record.

#### get_relationship

Retrieve the ID of customer user address record which was used to fill in a specific order address record.

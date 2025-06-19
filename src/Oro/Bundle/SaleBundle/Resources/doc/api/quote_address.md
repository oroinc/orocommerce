# Oro\Bundle\SaleBundle\Entity\QuoteAddress

## ACTIONS

### get

Retrieve a specific quote shipping address record.

{@inheritdoc}

### get_list

Retrieve a collection of quote shipping address records.

{@inheritdoc}

### create

Create a new quote shipping address record.

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
    "type": "quoteshippingaddresses",
    "attributes": {
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

Edit a specific quote shipping address record.

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
    "type": "quoteshippingaddresses",
    "id": "1",
    "attributes": {
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

Delete a specific quote shipping address record.

{@inheritdoc}

### delete_list

Delete a collection of quote shipping address records.

{@inheritdoc}

## FIELDS

### country

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### quote

The quote to which this address belongs to.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

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

Retrieve the ID of country record assigned to a specific address record.

### region

#### get_subresource

Retrieve a record of region assigned to a specific region record.

#### get_relationship

Retrieve IDs of region records assigned to a specific region record.

### customerAddress

#### get_subresource

Retrieve a record of customer address which was used to fill in a specific quote address record.

#### get_relationship

Retrieve the ID of customer address record which was used to fill in a specific quote address record.

### customerUserAddress

#### get_subresource

Retrieve a record of customer user address which was used to fill in a specific quote address record.

#### get_relationship

Retrieve the ID of customer user address record which was used to fill in a specific quote address record.

### quote

#### get_subresource

Retrieve the quote record a specific address record belongs to.

#### get_relationship

Retrieve the ID of the quote record which a specific address record belongs to.

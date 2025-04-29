# Oro\Bundle\SaleBundle\Entity\QuoteProduct

## ACTIONS

### get

Retrieve a specific quote product record.

{@inheritdoc}

### get_list

Retrieve a collection of quote product records.

{@inheritdoc}

### create

Create a new quote product record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "quoteproducts",
    "attributes": {
      "comment": "Some comment"
    },
    "relationships": {
      "quote": {
        "data": {
          "type": "quotes",
          "id": "1"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "10"
        }
      },
      "quoteProductOffers": {
        "data": [
          {
            "type": "quoteproductoffers",
            "id": "1"
          }
        ]
      }
    }
  }
}
```
{@/request}

### update

Edit a specific quote product record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "quoteproducts",
    "id": "94",
    "attributes": {
      "comment": "Some comment"
    }
  }
}
```
{@/request}

### delete

Delete a specific quote product record.

{@inheritdoc}

### delete_list

Delete a collection of quote product records.

{@inheritdoc}

## FIELDS

### quote

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### quoteProductOffers

#### create

{@inheritdoc}

**The required field. At least one offer should be added.**

#### update

{@inheritdoc}

**The product should have at least one offer.**

## SUBRESOURCES

### product

#### get_subresource

Retrieve a record of product assigned to a specific quote product record.

#### get_relationship

Retrieve the ID of product record assigned to a specific quote product record.

#### update_relationship

Replace product assigned to a specific quote product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "1"
  }
}
```
{@/request}

### quote

#### get_subresource

Retrieve the quote record a specific quote product record is assigned to.

#### get_relationship

Retrieve the ID of the quote record which a specific quote product record is assigned to.

### quoteProductOffers

#### get_subresource

Retrieve records of quote product offers item assigned to a specific quote product record.

#### get_relationship

Retrieve the IDs of quote product offer records assigned to a specific quote product record.

### quoteProductRequests

#### get_subresource

Retrieve records of quote product requests item assigned to a specific quote product record.

#### get_relationship

Retrieve the IDs of quote product request records assigned to a specific quote product record.

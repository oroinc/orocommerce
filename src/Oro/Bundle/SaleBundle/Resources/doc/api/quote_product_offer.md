# Oro\Bundle\SaleBundle\Entity\QuoteProductOffer

## ACTIONS

### get

Retrieve a specific quote product offer record.

{@inheritdoc}

### get_list

Retrieve a collection of quote product offer records.

{@inheritdoc}

### create

Create a new quote product offer record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "quoteproductoffers",
    "attributes": {
      "quantity": 1,
      "value": "13.5900",
      "currency": "USD"
    },
    "relationships": {
      "quoteProduct": {
        "data": {
          "type": "quoteproducts",
          "id": "1"
        }
      },
      "productUnit": {
        "data": {
          "type": "productunits",
          "id": "item"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific quote product offer record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "quoteproductoffers",
    "id": "2",
    "attributes": {
      "quantity": 2,
      "value": "14.5900",
      "currency": "USD"
    },
    "relationships": {
      "productUnit": {
        "data": {
          "type": "productunits",
          "id": "item"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific quote product offer record.

{@inheritdoc}

### delete_list

Delete a collection of quote product offer records.

{@inheritdoc}

## FIELDS

### quoteProduct

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### productUnit

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### quantity

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### productUnitCode

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### checksum

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### quoteProduct

#### get_subresource

Retrieve a record of quote product assigned to a specific quote product request record.

#### get_relationship

Retrieve the ID of quote product record assigned to a specific quote product request record.

### productUnit

#### get_subresource

Retrieve a record of product unit assigned to a specific quote product request record.

#### get_relationship

Retrieve the ID of product unit record assigned to a specific quote product request record.

#### update_relationship

Replace the product unit assigned to a specific quote product request record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunits",
    "id": "item"
  }
}
```
{@/request}

# Oro\Bundle\SaleBundle\Entity\QuoteProductRequest

## ACTIONS

### get

Retrieve a specific quote product request record.

{@inheritdoc}

### get_list

Retrieve a collection of quote product request records.

{@inheritdoc}

### create

Create a new quote product request record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "quoteproductrequests",
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

Edit a specific quote product request record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "quoteproductrequests",
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

Delete a specific quote product request record.

{@inheritdoc}

### delete_list

Delete a collection of line item records.

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

### productUnitCode

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### requestProductItem

#### update

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

### requestProductItem

#### get_subresource

Retrieve a record of RFQ product line item assigned to a specific quote product request record.

#### get_relationship

Retrieve the ID of RFQ product line item record assigned to a specific quote product request record.

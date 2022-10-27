# Oro\Bundle\RFPBundle\Entity\RequestProductItem

## ACTIONS

### get

Retrieve a specific request product item record.

{@inheritdoc}

### get_list

Retrieve a collection of request product item records.

{@inheritdoc}

### create

Create a new request product item record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqproductitems",
    "attributes": {
      "quantity": 99,
      "value": "1.0000",
      "currency": "USD"
    },
    "relationships": {
      "requestProduct": {
        "data": {
          "type": "rfqproducts",
          "id": "1"
        }
      },
      "productUnit": {
        "data": {
          "type": "productunits",
          "id": "set"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific request product item record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqproductitems",
    "id": "1",
    "attributes": {
      "quantity": 99,
      "value": "1.0000",
      "currency": "USD"
    },
    "relationships": {
      "requestProduct": {
        "data": {
          "type": "rfqproducts",
          "id": "1"
        }
      },
      "productUnit": {
        "data": {
          "type": "productunits",
          "id": "set"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific request product item record.

{@inheritdoc}

### delete_list

Delete a collection of request product item records.

{@inheritdoc}

## FIELDS

### value

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### currency

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### requestProduct

#### create

{@inheritdoc}

**The required field.**

### productUnit

#### create

{@inheritdoc}

**The required field.**

## SUBRESOURCES

### productUnit

#### get_subresource

Retrieve a record of product unit assigned to a specific request product item record.

#### get_relationship

Retrieve the ID of product unit record assigned to a specific request product item record.

#### update_relationship

Replace product unit assigned to a specific request product item record.

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

### requestProduct

#### get_subresource

Retrieve the request product record a specific request product item record is assigned to.

#### get_relationship

Retrieve the ID of request product record a specific request product item record is assigned to.

#### update_relationship

Replace the request product record a specific request product item record is assigned to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqproducts",
    "id": "1"
  }
}
```
{@/request}

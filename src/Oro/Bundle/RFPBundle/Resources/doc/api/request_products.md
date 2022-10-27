# Oro\Bundle\RFPBundle\Entity\RequestProduct

## ACTIONS

### get

Retrieve a specific request product record.

{@inheritdoc}

### get_list

Retrieve a collection of request product records.

{@inheritdoc}

### create

Create a new request product record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqproducts",
    "id": "1",
    "attributes": {
      "comment": "Notes 0"
    },
    "relationships": {
      "request": {
        "data": {
          "type": "rfqs",
          "id": "1"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "10"
        }
      },
      "requestProductItems": {
        "data": [
          {
            "type": "rfqproductitems",
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

Edit a specific request product record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqproducts",
    "id": "94",
    "attributes": {
      "comment": "Notes 0"
    },
    "relationships": {
      "request": {
        "data": {
          "type": "rfqs",
          "id": "1"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "10"
        }
      },
      "requestProductItems": {
        "data": [
          {
            "type": "rfqproductitems",
            "id": "1"
          }
        ]
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific request product record.

{@inheritdoc}

### delete_list

Delete a collection of request product records.

{@inheritdoc}

## FIELDS

### request

#### create

{@inheritdoc}

**The required field.**

### product

#### create

{@inheritdoc}

**The required field.**

### requestProductItems

#### create

{@inheritdoc}

**The required field.**

## SUBRESOURCES

### product

#### get_subresource

Retrieve a record of product assigned to a specific request product record.

#### get_relationship

Retrieve the ID of product record assigned to a specific request product record.

#### update_relationship

Replace product assigned to a specific request product record.

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

### request

#### get_subresource

Retrieve the request records a specific request product record is assigned to.

#### get_relationship

Retrieve the ID of the request record which a specific request product record is assigned to.

#### update_relationship

Replace request assigned to a specific request product record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqs",
    "id": "1"
  }
}
```
{@/request}

### requestProductItems

#### get_subresource

Retrieve records of a request product item assigned to a specific request product record.

#### get_relationship

Retrieve the IDs of request product item records assigned to a specific request product record.

#### update_relationship

Replace the list of request product item records assigned to a specific request product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "rfqproductitems",
      "id": "2"
    },
    {
      "type": "rfqproductitems",
      "id": "3"
    }
  ]
}
```
{@/request}

#### add_relationship

Set request product item records for a specific request product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "rfqproductitems",
      "id": "2"
    },
    {
      "type": "rfqproductitems",
      "id": "3"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove request product item records from a specific request product record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "rfqproductitems",
      "id": "2"
    },
    {
      "type": "rfqproductitems",
      "id": "3"
    }
  ]
}
```
{@/request}

# Oro\Bundle\OrderBundle\Entity\OrderShippingTracking

## ACTIONS

### get

Retrieve a specific order shipping tracking record.

{@inheritdoc}

### get_list

Retrieve a collection of order shipping tracking records.

{@inheritdoc}

### create

Create a new order shipping tracking record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "ordershippingtrackings",
    "attributes": {
      "method": "direct",
      "number": "12345"
    },
    "relationships": {
      "orders": {
        "data": [
          {
            "type": "orders",
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

Edit a specific order shipping tracking record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "ordershippingtrackings",
    "id": "1",
    "attributes": {
      "method": "direct",
      "number": "12345"
    },
    "relationships": {
      "orders": {
        "data": [
          {
            "type": "orders",
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

Delete a specific order shipping tracking record.

{@inheritdoc}

### delete_list

Delete a collection of order shipping tracking records.

{@inheritdoc}

## FIELDS

### number

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### method

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### orders

#### create

{@inheritdoc}

**The required field.**

## SUBRESOURCES

### orders

#### get_subresource

Retrieve a record of shipping tracking assigned to a specific orders record.

#### get_relationship

Retrieve IDs of shipping tracking records assigned to a specific orders record.

#### update_relationship

Replace the list of orders assigned to a specific shipping tracking record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orders",
      "id": "2"
    }
  ]
}
```
{@/request}

#### add_relationship

Set order records for a specific shipping tracking record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orders",
      "id": "2"
    },
    {
      "type": "orders",
      "id": "3"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove order records from a specific shipping tracking record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orders",
      "id": "1"
    }
  ]
}
```
{@/request}

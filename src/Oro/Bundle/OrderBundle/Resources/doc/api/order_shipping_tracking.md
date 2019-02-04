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
      "order": {
        "data": {
          "type": "orders",
          "id": "1"
        }
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
      "order": {
        "data": {
          "type": "orders",
          "id": "1"
        }
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

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### method

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### order

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### order

#### get_subresource

Retrieve a record of shipping tracking assigned to a specific order record.

#### get_relationship

Retrieve ID of shipping tracking records assigned to a specific order record.

#### update_relationship

Replace shipping tracking assigned to a specific order record

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orders",
    "id": "1"
  }
}
```
{@/request}

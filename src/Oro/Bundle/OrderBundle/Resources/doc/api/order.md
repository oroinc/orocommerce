# Oro\Bundle\OrderBundle\Entity\Order

## ACTIONS

### get

Retrieve a specific order record.

{@inheritdoc}

### get_list

Retrieve a collection of order records.

{@inheritdoc}

### create

Create a new order record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orders",
    "attributes": {
      "identifier": "FR1012401Z",
      "poNumber": "CV032342USDD",
      "customerNotes": "Please, call before delivery",
      "shipUntil": "2017-08-15",
      "currency": "USD"
    },
    "relationships": {
      "billingAddress": {
        "data": {
          "type": "orderaddresses",
          "id": "18"
        }
      },
      "website": {
        "data": {
          "type": "websites",
          "id": "1"
        }
      },
      "lineItems": {
        "data": [
          {
            "type": "orderlineitems",
            "id": "1"
          }
        ]
      },
      "customer": {
        "data": {
          "type": "customers",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific order record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orders",
    "id": "1",
    "attributes": {
      "poNumber": "CV032342USDD",
      "customerNotes": "Please, call before delivery",
      "shipUntil": "2017-08-15"
    },
    "relationships": {      
      "website": {
        "data": {
          "type": "websites",
          "id": "1"
        }
      },
      "lineItems": {
        "data": [
          {
            "type": "orderlineitems",
            "id": "1"
          }
        ]
      },
      "customer": {
        "data": {
          "type": "customers",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific order record.

{@inheritdoc}

### delete_list

Delete a collection of order records.

{@inheritdoc}

## FIELDS

### customer

#### create

{@inheritdoc}

**The required field**

### lineItems

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### billingAddress

#### get_subresource

Retrieve a record of address assigned to a specific order record.

#### get_relationship

Retrieve ID of address record assigned to a specific order record.

#### update_relationship

Replace address assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderaddresses",
    "id": "1"
  }
}
```
{@/request}

### customer

#### get_subresource

Retrieve a record of customer assigned to a specific order record.

#### get_relationship

Retrieve ID of customer record assigned to a specific order record.

#### update_relationship

Replace the customer assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customers",
    "id": "1"
  }
}
```
{@/request}

### customerUser

#### get_subresource

Retrieve a record of customer user assigned to a specific order record.

#### get_relationship

Retrieve ID of customer user record assigned to a specific order record.

#### update_relationship

Replace customer user assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "customerusers",
    "id": "1"
  }
}
```
{@/request}

### discounts

#### get_subresource

Retrieve a records of discount assigned to a specific order record.

#### get_relationship

Retrieve IDs of discount records assigned to a specific order record.

#### update_relationship

Replace the list of discount assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderdiscounts",
      "id": "6"
    }
  ]
}
```
{@/request}

#### add_relationship

Set discount records for a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderdiscounts",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove discount records from a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderdiscounts",
      "id": "2"
    }
  ]
}
```
{@/request}

### lineItems

#### get_subresource

Retrieve a record of line item assigned to a specific order record.

#### get_relationship

Retrieve IDs of line item records assigned to a specific order record.

#### update_relationship

Replace the list of line item assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderlineitems",
      "id": "2"
    }
  ]
}
```
{@/request}

#### add_relationship

Set line item records for a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderlineitems",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove line item records from a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "orderlineitems",
      "id": "1"
    }
  ]
}
```
{@/request}

### organization

#### get_subresource

Retrieve the record of the organization a specific order record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific order record will belong to.

#### update_relationship

Replace the organization a specific order record belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "organizations",
    "id": "1"
  }
}
```
{@/request}

### owner

#### get_subresource

Retrieve the record of the user who is an owner of a specific order record.

#### get_relationship

Retrieve the ID of the user who is an owner of a specific order record.

#### update_relationship

Replace the owner of a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "users",
    "id": "1"
  }
}
```
{@/request}

### shippingAddress

#### get_subresource

Retrieve a record of address assigned to a specific order record.

#### get_relationship

Retrieve ID of address record assigned to a specific order record.

#### update_relationship

Replace address assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderaddresses",
    "id": "2"
  }
}
```
{@/request}

### shippingTrackings

#### get_subresource

Retrieve a record of shipping tracking assigned to a specific order record.

#### get_relationship

Retrieve IDs of shipping tracking records assigned to a specific order record.

#### update_relationship

Replace the list of shipping tracking assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "ordershippingtrackings",
      "id": "3"
    }
  ]
}
```
{@/request}

#### add_relationship

Set shipping tracking records for a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "ordershippingtrackings",
      "id": "3"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove shipping tracking records from a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "ordershippingtrackings",
      "id": "3"
    }
  ]
}
```
{@/request}

### website

#### get_subresource

Retrieve a record of website assigned to a specific order record.

#### get_relationship

Retrieve ID of website record assigned to a specific order record.

#### update_relationship

Replace website assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "websites",
    "id": "1"
  }
}
```
{@/request}

### warehouse

#### get_subresource

Retrieve a record of warehouse assigned to a specific order record.

#### get_relationship

Retrieve ID of warehouse record assigned to a specific order record.

#### update_relationship

Replace warehouse assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "warehouses",
    "id": "1"
  }
}
```
{@/request}

### paymentTerm

#### get_subresource

Retrieve a record of payment term assigned to a specific order record.

#### get_relationship

Retrieve ID of payment term record assigned to a specific order record.

#### update_relationship

Replace the payment term assigned to a specific order record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "paymentterms",
    "id": "2"
  }
}
```
{@/request}

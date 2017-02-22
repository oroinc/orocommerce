# Oro\Bundle\RFPBundle\Entity\Request

## ACTIONS

### get

Get one Request record.

{@inheritdoc}

### get_list

Returns a collection of Requests

{@inheritdoc}

### create

Create a new Request. The fields `internal_status`, `customer_status`, `requestAdditionalNotes` are not configurable 
by api, because it depends on the workflow states. If you want to create Request together with a related resources such as
`requestproductitems`, `requestprodict` you can use the included section of a JSON request body. 
Please take a look at the following example:

```
{"data": {
    "id": "8da4d8e7-6b25-4c5c-faff-b510f7bbb84f",
    "type": "requests",
    "attributes": {"firstName": "Ronald", "lastName": "Rivera", "email": "rrivera0@live.com", "company": "Centidel"},
    "relationships": {
      "requestProducts": {
        "data": [{"type": "requestproducts", "id": "8da4d8e7-6b25-4c5c-8075-b510f7bbb84f"}]
      }
    }
  },
  "included": [
    {
      "type": "requestproducts",
      "id": "8da4d8e7-6b25-4c5c-8075-b510f7bbb84f",
      "attributes": {
        "comment": "Comment"
      },
      "relationships": {
        "request": {
          "data": {"type": "requests", "id": "8da4d8e7-6b25-4c5c-faff-b510f7bbb84f"}
        },
        "product": {
          "data": {"type": "products", "id": "1"}
        },
        "requestProductItems": {
          "data": [{"type": "requestproductitems", "id": "707dda0d-35f5-47b9-b2ce-a3e92b9fdee7"}]
        }
      }
    },
    {
      "type": "requestproductitems",
      "id": "707dda0d-35f5-47b9-b2ce-a3e92b9fdee7",
      "attributes": {
        "quantity": 35,
        "value": "35.0000",
        "currency": "USD"
      },
      "relationships": {
        "productUnit": {
          "data": {"type": "productunits", "id": "set"}
        },
        "requestProduct": {
          "data": {"type": "requestproducts", "id": "8da4d8e7-6b25-4c5c-8075-b510f7bbb84f"}
        }
      }
    }
  ]
}
```

{@inheritdoc}

### update

Update existing Request record. The fields `internal_status`, `customer_status`, `requestAdditionalNotes`, 
`createdAt` - can not be updated. If you want to add new related resources: `requestprodict` you can use the included
section of a JSON request body. Please take a look at the following example:


```
{"data": {
    "id": "1",
    "type": "requests",
    "relationships": {
      "requestProducts": {
        "data": [{"type": "requestproducts", "id": "8da4d8e7-6b25-4c5c-8075-b510f7bbb84f"}]
      }
    }
  },
  "included": [
    {
      "type": "requestproducts",
      "id": "8da4d8e7-6b25-4c5c-8075-b510f7bbb84f",
      "attributes": {
        "comment": "Comment"
      },
      "relationships": {
        "request": {
          "data": {"type": "requests", "id": "1"}
        },
        "product": {
          "data": {"type": "products", "id": "1"}
        },
        "requestProductItems": {
          "data": [{"type": "requestproductitems", "id": "707dda0d-35f5-47b9-b2ce-a3e92b9fdee7"}]
        }
      }
    },
    {
      "type": "requestproductitems",
      "id": "707dda0d-35f5-47b9-b2ce-a3e92b9fdee7",
      "attributes": {
        "quantity": 35,
        "value": "35.0000",
        "currency": "USD"
      },
      "relationships": {
        "productUnit": {
          "data": {"type": "productunits", "id": "set"}
        },
        "requestProduct": {
          "data": {"type": "requestproducts", "id": "8da4d8e7-6b25-4c5c-8075-b510f7bbb84f"}
        }
      }
    }
  ]
}
```

{@inheritdoc}

### delete

{@inheritdoc}

### delete_list

{@inheritdoc}

## FIELDS

### firstName

#### create

{@inheritdoc}

**The required field**

### lastName

#### create

{@inheritdoc}

**The required field**

### email

#### create

{@inheritdoc}

**The required field**

### company

#### create

{@inheritdoc}

**The required field**

## SUBRESOURCES

### requestProducts

#### get_subresource

Get full information about the product of request for quote.

### contact

#### get_subresource

Get full information about the person on the customer side who is directly related to the opportunity.

### requestAdditionalNotes

#### get_subresource

Get list of additional notes.

### assignedUsers

#### get_subresource

Get full information about assigned users.

### organization

#### get_subresource

Get full information about an organization to which the request belongs.

### owner

#### get_subresource

Get full information about an user who owns the request.

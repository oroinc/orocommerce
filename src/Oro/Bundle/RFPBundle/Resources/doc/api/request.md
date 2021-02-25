# Oro\Bundle\RFPBundle\Entity\Request

## ACTIONS

### get

Retrieve a specific request record.

{@inheritdoc}

### get_list

Retrieve a collection of request records.

{@inheritdoc}

### create

Create a new request record.

The created record is returned in the response.

{@inheritdoc}

**Note:**
The fields **internal_status**, **customer_status**, **requestAdditionalNotes** are not configurable by API, because they depend on the workflow states.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqs",    
    "attributes": { 
      "firstName": "Ronald",
      "lastName": "Rivera",
      "email": "rrivera0@live.com",
      "phone": "2-(999)507-4625",
      "company": "Centidel",
      "role": "senior manager",
      "note": "Pellentesque at nulla.",
      "poNumber": "CA9134USD",
      "shipUntil": "2017-09-02"
    },
    "relationships": {
      "requestProducts": {
        "data": [
          {
            "type": "rfqproducts",
            "id": "1"
          }
        ]
      },      
      "customerUser": {
        "data": {
          "type": "customerusers",
          "id": "5"
        }
      },
      "customer": {
        "data": {
          "type": "customers",
          "id": "2"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific request record.

The updated record is returned in the response.

{@inheritdoc}

**Note:**
The fields **internal_status**, **customer_status**, **requestAdditionalNotes** are not configurable by API, because they depend on the workflow states.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqs",
    "id": "1",
    "attributes": { 
      "firstName": "Ronald",
      "lastName": "Rivera",
      "email": "rrivera0@live.com",
      "phone": "2-(999)507-4625",
      "company": "Centidel",
      "role": "senior manager",
      "note": "Pellentesque at nulla.",
      "poNumber": "CA9134USD",
      "shipUntil": "2017-09-02"
    },
    "relationships": {
      "requestProducts": {
        "data": [
          {
            "type": "rfqproducts",
            "id": "1"
          }
        ]
      },      
      "customerUser": {
        "data": {
          "type": "customerusers",
          "id": "5"
        }
      },
      "customer": {
        "data": {
          "type": "customers",
          "id": "2"
        }
      }
    }
  }
}
```
{@/request}

### delete

Delete a specific request record.

{@inheritdoc}

### delete_list

Delete a collection of request records.

{@inheritdoc}

## FIELDS

### firstName

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### lastName

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### email

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### company

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### customer_status

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### internal_status

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### requestAdditionalNotes

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### assignedUsers

#### get_subresource

Retrieve a record of assigned user assigned to a specific request record.

#### get_relationship

Retrieve IDs of assigned user records assigned to a specific request record.

#### update_relationship

Replace the list of assigned user assigned to a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    },
    {
      "type": "users",
      "id": "2"
    }
  ]
}
```
{@/request}

#### add_relationship

Set assigned user records for a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    },
    {
      "type": "users",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove assigned user records from a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "users",
      "id": "1"
    },
    {
      "type": "users",
      "id": "2"
    }
  ]
}
```
{@/request}

### assignedCustomerUsers

#### get_subresource

Retrieve a record of assigned customer user assigned to a specific request record.

#### get_relationship

Retrieve IDs of assigned customer user records assigned to a specific request record.

#### update_relationship

Replace the list of assigned customer user assigned to a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "customerusers",
      "id": "1"
    },
    {
      "type": "customerusers",
      "id": "2"
    }
  ]
}
```
{@/request}

#### add_relationship

Set assigned customer user records for a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "customerusers",
      "id": "1"
    },
    {
      "type": "customerusers",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove assigned customer user records from a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "customerusers",
      "id": "1"
    },
    {
      "type": "customerusers",
      "id": "2"
    }
  ]
}
```
{@/request}

### requestAdditionalNotes

#### get_subresource

Retrieve a record of request additional note assigned to a specific request record.

#### get_relationship

Retrieve IDs of request additional note records assigned to a specific request record.

#### update_relationship

Replace the list of request additional note assigned to a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "rfqadditionalnotes",
      "id": "1"
    },
    {
      "type": "rfqadditionalnotes",
      "id": "2"
    }
  ]
}
```
{@/request}

#### add_relationship

Set request additional note records for a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "rfqadditionalnotes",
      "id": "1"
    },
    {
      "type": "rfqadditionalnotes",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove request additional note records from a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "rfqadditionalnotes",
      "id": "1"
    },
    {
      "type": "rfqadditionalnotes",
      "id": "2"
    }
  ]
}
```
{@/request}

### requestProducts

#### get_subresource

Retrieve a record of request product assigned to a specific request record.

#### get_relationship

Retrieve IDs of request product records assigned to a specific request record.

#### update_relationship

Replace the list of request product assigned to a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "rfqproducts",
      "id": "1"
    },
    {
      "type": "rfqproducts",
      "id": "2"
    }
  ]
}
```
{@/request}

#### add_relationship

Set request product records for a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "rfqproducts",
      "id": "1"
    },
    {
      "type": "rfqproducts",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove request product records from a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "rfqproducts",
      "id": "1"
    },
    {
      "type": "rfqproducts",
      "id": "2"
    }
  ]
}
```
{@/request}

### customer

#### get_subresource

Retrieve the customer records a specific request record is assigned to.

#### get_relationship

Retrieve the IDs of the customer records which a specific request record is assigned to.

#### update_relationship

Replace customer  a specific request record is assigned to.

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

Retrieve the customer user records a specific request record is assigned to.

#### get_relationship

Retrieve the IDs of the customer user records which a specific request record is assigned to.

#### update_relationship

Replace customer user a specific request record is assigned to.

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

### customer_status

#### get_subresource

Retrieve a record of customer status assigned to a specific request record.

#### get_relationship

Retrieve ID of customer status record assigned to a specific request record.

#### update_relationship

Replace the list of customer status assigned to a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqcustomerstatuses",
    "id": "cancelled"
  }
}
```
{@/request}

### internal_status

#### get_subresource

Retrieve a record of internal status assigned to a specific request record.

#### get_relationship

Retrieve ID of internal status record assigned to a specific request record.

#### update_relationship

Replace the list of internal status assigned to a specific request record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "rfqinternalstatuses",
    "id": "cancelled_by_customer"
  }
}
```
{@/request}

### organization

#### get_subresource

Retrieve the record of the organization a specific request record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific request record will belong to.

#### update_relationship

Replace the organization a specific request record belongs to.

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

Retrieve the record of the user who is an owner of a specific request record.

#### get_relationship

Retrieve the ID of the user who is an owner of a specific request record.

#### update_relationship

Replace the owner of a specific request record.

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


# Extend\Entity\EV_Rfp_Customer_Status

## ACTIONS

### get

Retrieve a specific request for quote customer status record.

The request for quote statuses visible to the buyer.

### get_list

Retrieve a collection of request for quote customer status records.

The request for quote statuses visible to the buyer.

# Extend\Entity\EV_Rfp_Internal_Status

## ACTIONS

### get

Retrieve a specific request for quote internal status record.

The request for quote statuses visible in the management console.

### get_list

Retrieve a collection of request for quote internal status records.

The request for quote statuses visible in the management console.

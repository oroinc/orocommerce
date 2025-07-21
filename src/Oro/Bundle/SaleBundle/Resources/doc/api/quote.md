# Oro\Bundle\SaleBundle\Entity\Quote

## ACTIONS

### get

Retrieve a specific quote record.

{@inheritdoc}

### get_list

Retrieve a collection of quote records.

{@inheritdoc}

### create

Create a new quote record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "quotes",    
    "attributes": { 
      "poNumber": "CA9134USD",
      "shipUntil": "2017-09-02"
    },
    "relationships": {
      "quoteProducts": {
        "data": [
          {
            "type": "quoteproducts",
            "id": "quote_product_1"
          }
        ]
      },      
      "shippingAddress": {
        "data": {
          "type": "quoteshippingaddresses",
          "id": "shipping_address"
        }
      }
    }
  },
  "included": [
    {
      "type": "quoteproducts",
      "id": "quote_product_1",
      "attributes": {
        "comment": "Some notes"
      },
      "relationships": {
        "product": {
          "data": {
            "type": "products",
            "id": "43"
          }
        },
        "quoteProductOffers": {
          "data": [
            {
              "type": "quoteproductoffers",
              "id": "quote_product_1_offer_1"
            }
          ]
        }
      }
    },
    {
      "type": "quoteproductoffers",
      "id": "quote_product_1_offer_1",
      "attributes": {
        "quantity": 5,
        "value": "20.0000",
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
    },
    {
      "type": "quoteshippingaddresses",
      "id": "shipping_address",
      "attributes": {
        "street": "1215 Caldwell Road",
        "city": "Rochester",
        "postalCode": "14608",
        "firstName": "Amanda",
        "lastName": "Cole"
      },
      "relationships": {
        "country": {
          "data": {
            "type": "countries",
            "id": "US"
          }
        },
        "region": {
          "data": {
            "type": "regions",
            "id": "US-NY"
          }
        }
      }
    }
  ]
}
```
{@/request}

### update

Edit a specific quote record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "quotes",
    "id": "1",
    "attributes": {
      "poNumber": "CA9134USD"
    }
  }
}
```
{@/request}

### delete

Delete a specific quote record.

{@inheritdoc}

### delete_list

Delete a collection of quote records.

{@inheritdoc}

## FIELDS

### identifier

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### guestAccessId

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### currency

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### customerStatus

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### internalStatus

#### create, update

{@inheritdoc}

**The read-only field when there is an active workflow for quotes. A passed value will be ignored.**

### pricesChanged

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### shippingAddress

#### create

{@inheritdoc}

**Only new address can be passed.**

#### update

{@inheritdoc}

**Cannot be changed once already set.**

### request

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### assignedUsers

#### get_subresource

Retrieve records of an order fulfillment officers assigned to a specific quote record.

#### get_relationship

Retrieve the IDs of an order fulfillment officers records assigned to a specific quote record.

#### update_relationship

Replace the list of an order fulfillment officers assigned to a specific quote record.

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

Set an order fulfillment officers records for a specific quote record.

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

Remove an order fulfillment officers records from a specific quote record.

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

Retrieve records of customer users that will receive the order delivery assigned to a specific quote record.

#### get_relationship

Retrieve the IDs of customer users that will receive the order delivery records assigned to a specific quote record.

#### update_relationship

Replace the list of customer users that will receive the order delivery assigned to a specific quote record.

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

Set customer users that will receive the order delivery records for a specific quote record.

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

Remove customer users that will receive the order delivery records from a specific quote record.

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

### quoteProducts

#### get_subresource

Retrieve records of quoted products assigned to a specific quote record.

#### get_relationship

Retrieve the IDs of quoted products records assigned to a specific quote record.

#### update_relationship

Replace the list of quoted products assigned to a specific quote record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "quoteproducts",
      "id": "1"
    },
    {
      "type": "quoteproducts",
      "id": "2"
    }
  ]
}
```
{@/request}

#### add_relationship

Set quoted products records for a specific quote record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "quoteproducts",
      "id": "1"
    },
    {
      "type": "quoteproducts",
      "id": "2"
    }
  ]
}
```
{@/request}

#### delete_relationship

Remove quoted products records from a specific quote record.

{@request:json_api}
Example:

```JSON
{
  "data": [
    {
      "type": "quoteproducts",
      "id": "1"
    },
    {
      "type": "quoteproducts",
      "id": "2"
    }
  ]
}
```
{@/request}

### customer

#### get_subresource

Retrieve a record of customer assigned to a specific quote record.

#### get_relationship

Retrieve the ID of customer record assigned to a specific quote record.

#### update_relationship

Replace the customer assigned to a specific quote record.

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

Retrieve a record of customer user assigned to a specific quote record.

#### get_relationship

Retrieve the ID of customer user record assigned to a specific quote record.

#### update_relationship

Replace customer user assigned to a specific quote record.

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

### customerStatus

#### get_subresource

Retrieve a record of customer status assigned to a specific quote record.

#### get_relationship

Retrieve the ID of customer status record assigned to a specific quote record.

### internalStatus

#### get_subresource

Retrieve a record of internal status assigned to a specific quote record.

#### get_relationship

Retrieve the ID of internal status record assigned to a specific quote record.

#### update_relationship

Change the internal status assigned to a specific quote.

**Note:**
The internal status cannot be changed when there is an active workflow for quotes.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderinternalstatuses",
    "id": "draft"
  }
}
```
{@/request}

### organization

#### get_subresource

Retrieve a record of the organization a specific quote record belongs to.

#### get_relationship

Retrieve the ID of the organization record which a specific quote record belongs to.

#### update_relationship

Replace the organization a specific quote record belongs to.

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

Retrieve a record of the user who is an owner of a specific quote record.

#### get_relationship

Retrieve the ID of the user who is an owner of a specific quote record.

#### update_relationship

Replace the owner of a specific quote record.

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

Retrieve a record of shipping address assigned to a specific quote record.

#### get_relationship

Retrieve the ID of shipping address record assigned to a specific quote record.

### paymentTerm

#### get_subresource

Retrieve a record of payment term assigned to a specific quote record.

#### get_relationship

Retrieve the ID of payment term record assigned to a specific quote record.

#### update_relationship

Replace the payment term assigned to a specific quote record.

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

### opportunity

#### get_subresource

Retrieve a record of the opportunity a specific quote record is created from.

#### get_relationship

Retrieve the ID of the opportunity record which a specific quote record is created from.

#### update_relationship

Replace the opportunity a specific quote record is created from.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "opportunities",
    "id": "1"
  }
}
```
{@/request}

### request

#### get_subresource

Retrieve a record of the RFQ a specific quote record is created from.

#### get_relationship

Retrieve the ID of the RFQ record which a specific quote record is created from.

### website

#### get_subresource

Retrieve a record of website assigned to a specific quote record.

#### get_relationship

Retrieve the IDs of website records assigned to a specific quote record.

#### update_relationship

Replace the website record assigned to a specific quote record.

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


# Extend\Entity\EV_Quote_Customer_Status

## ACTIONS

### get

Retrieve a specific quote customer status record.

The quote customer status reflects the step of its management in the front store.

### get_list

Retrieve a collection of quote customer status records.

The quote customer status reflects the step of its management in the front store.


# Extend\Entity\EV_Quote_Internal_Status

## ACTIONS

### get

Retrieve a specific quote internal status record.

The quote internal status reflects the step of its backoffice management.

### get_list

Retrieve a collection of quote internal status record.

The quote internal status reflects the step of its backoffice management.

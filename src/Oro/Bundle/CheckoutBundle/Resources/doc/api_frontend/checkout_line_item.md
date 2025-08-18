# Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem

## ACTIONS

### get

Retrieve a specific checkout line item record.

{@inheritdoc}

### get_list

Retrieve a collection of checkout line item records.

{@inheritdoc}

### create

Create a new checkout line item record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "checkoutlineitems",
    "attributes": {
      "quantity": 10
    },
    "relationships": {
      "checkout": {
        "data": {
          "type": "checkouts",
          "id": "1"
        }
      },
      "product": {
        "data": {
          "type": "products",
          "id": "2"
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

Edit a specific checkout line item record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "checkoutlineitems",
    "id": "1",
    "attributes": {
      "quantity": 10
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

Delete a specific checkout line item record.

{@inheritdoc}

### delete_list

Delete a collection of checkout line item records.

{@inheritdoc}

## FIELDS

### price

Price for a product unit that is used in this checkout.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### currency

A currency for a product price.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### subTotal

The product price multiplied by the quantity.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### freeFormProduct

#### create, update

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

### quantity

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### product

#### create, update

{@inheritdoc}

**Note:**
This value can be omitted if the **productSku** field is specified in the request.

### kitItemLineItems

{@inheritdoc}

#### create, update

**The field is required if base product is a kit**

### checksum

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### shippingEstimateAmount

{@inheritdoc}

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### group

The group to which the checkout line item belongs.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### checkout

#### get_subresource

Retrieve the checkout record a specific line item record is assigned to.

#### get_relationship

Retrieve the ID of the checkout record which a specific line item record is assigned to.

### parentProduct

#### get_subresource

Retrieve a record of parent product assigned to a specific line item record.

#### get_relationship

Retrieve the ID of parent product record assigned to a specific line item record.

### product

#### get_subresource

Retrieve a record of product assigned to a specific line item record.

#### get_relationship

Retrieve the ID of product record assigned to a specific line item record.

### productUnit

#### get_subresource

Retrieve a record of product unit assigned to a specific line item record.

#### get_relationship

Retrieve the ID of product unit record assigned to a specific line item record.

### kitItemLineItems

#### get_subresource

Retrieve a list of checkout product kit item line item records assigned to a specific line item record.

#### get_relationship

Retrieve the IDs of checkout product kit item line item records assigned to a specific line item record.

### group

#### get_subresource

Retrieve a record of group a specific line item record belongs.

#### get_relationship

Retrieve the ID of group record a specific line item record belongs.

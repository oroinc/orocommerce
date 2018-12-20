# Oro\Bundle\OrderBundle\Entity\OrderDiscount

## ACTIONS

### get

Retrieve a specific discount record.

{@inheritdoc}

### get_list

Retrieve a collection of discount records.

{@inheritdoc}

### create

Create a new discount record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderdiscounts",
    "attributes": {
      "description": "Sale",
      "percent": 2.8421929223712,
      "amount": 100,
      "orderDiscountType": "oro_order_discount_item_type_amount"
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

Edit a specific discount record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "orderdiscounts",
    "id": "1",
    "attributes": {
      "description": "Sale",
      "percent": 3.0,
      "amount": 150     
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

Delete a specific discount record.

{@inheritdoc}

### delete_list

Delete a collection of discount records.

{@inheritdoc}

## FIELDS

### order

#### create

{@inheritdoc}

**The required field**

### orderDiscountType

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### amount

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

### percent

#### create

{@inheritdoc}

**The required field**

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

## SUBRESOURCES

### order

#### get_subresource

Retrieve the order record a specific discount record is assigned to.

#### get_relationship

Retrieve the ID of the order record which a specific discount record is assigned to.

#### update_relationship

Replace the order a specific discount record is assigned to.

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

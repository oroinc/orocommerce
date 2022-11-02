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
      "amount": 150     
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

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### orderDiscountType

{@inheritdoc}
Possible values: `oro_order_discount_item_type_amount`, `oro_order_discount_item_type_percent`.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**This field must not be empty, if it is passed.**

### amount

#### create

{@inheritdoc}

**Note:**
The field is required when the discount type is `oro_order_discount_item_type_amount`,
otherwise a passed value will be ignored.

#### update

{@inheritdoc}

**Note:**
This field must remain defined when the discount type is `oro_order_discount_item_type_amount`.

### percent

#### create

{@inheritdoc}

**Note:**
The field is required when the discount type is `oro_order_discount_item_type_percent`,
otherwise a passed value will be ignored.

#### update

{@inheritdoc}

**Note:**
This field must remain defined when the discount type is `oro_order_discount_item_type_percent`.

## SUBRESOURCES

### order

#### get_subresource

Retrieve the order record a specific discount record is assigned to.

#### get_relationship

Retrieve the ID of the order record which a specific discount record is assigned to.

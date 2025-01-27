# Oro\Bundle\CheckoutBundle\Api\Model\CheckoutLineItemGroup

## ACTIONS

### get

Retrieve a specific checkout line item group record.

### update

Edit a specific checkout line item group record.

The updated record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "checkoutlineitemgroups",
    "id": "MS1wcm9kdWN0LmNhdGVnb3J5OjQ=",
    "attributes": {
      "shippingMethod": "flat_rate_1",
      "shippingMethodType": "primary"
    }
  }
}
```
{@/request}

## FIELDS

### name

The group name.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### itemCount

The number of line items in the group.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### totalValue

The total amount of all line items in the group.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### currency

The currency in which the total value is calculated.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### shippingMethod

The shipping method selected for the order delivery.

### shippingMethodType

The shipping method type selected for the order delivery.

### shippingEstimateAmount

The shipping cost value calculated according to configured shipping rules and assigned line item group shipping method.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

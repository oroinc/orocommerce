# Oro\Bundle\CheckoutBundle\Entity\Checkout

## FIELDS

### coupons

The coupons allied to the checkout record.

Each item is an object with the following properties:

**couponCode** is a string that represents the coupon code.

**description** is a string that represents the coupon description.

Example of data: **\[{"couponCode": "SALE25", "description": "Seasonal Sale"}\]**

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### coupons

#### add_subresource

Apply a coupon to a specific checkout record.

{@request:json_api}
Example:

```JSON
{
  "meta": {
    "couponCode": "SALE25"
  }
}
```
{@/request}

#### delete_subresource

Remove a coupon applied to a specific checkout record.

{@request:json_api}
Example:

```JSON
{
  "meta": {
    "couponCode": "SALE25"
  }
}
```
{@/request}


# Oro\Bundle\PromotionBundle\Api\Model\ChangeCouponRequest

## FIELDS

### couponCode

The coupon code.

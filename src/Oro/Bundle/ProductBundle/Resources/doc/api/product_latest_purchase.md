# Oro\Bundle\ProductBundle\Api\Model\ProductLatestPurchase

## ACTIONS

### get_list

Retrieve a collection of the latest product purchase records.

**Note:**
The **product** filter is required, along with one of the following: **customer**, **hierarchicalCustomer**, or **customerUser**. The **customer** and **hierarchicalCustomer** filters cannot be used together.

## FIELDS

### currency

The product price currency.

### price

The product price.

### purchasedAt

The date and time of the latest purchase.

### product

The product.

### customer

The customer.

### customerUser

The customerUser.

### website

The website.

### unit

The product unit.

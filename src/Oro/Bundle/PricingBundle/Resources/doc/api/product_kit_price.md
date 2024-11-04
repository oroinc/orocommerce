# Oro\Bundle\PricingBundle\Api\Model\ProductKitPrice

## ACTIONS

### get_list

Retrieve a collection of kit product price records.

{@inheritdoc}

**Note:**
It is required to provide the **customer**, **product**, **website**, **unit** and **quantity** filters with request.
Also, depending on the product kit configuration, the resource can require **kit item quantity** and **kit item product** filters for kit-needed items.

## FIELDS

### currency

The kit product price currency.

### quantity

The kit product quantity.

### value

The kit product price.

### product

The kit product.

### customer

The customer. To get prices for an unauthorized user, use a **0** value.

### kitItemPrices

The list of kit item product prices.

### website

The website.

### unit

The unit of quantity.

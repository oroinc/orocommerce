# Oro\Bundle\PricingBundle\Api\Model\CustomerPrice

## ACTIONS

### get_list

Retrieve a collection of customer's product price records.

**Note:**
It is required to provide the **customer**, **product** and **website** filters with request.

## FIELDS

### currency

The product price currency.

### quantity

The product quantity.

### value

The product price.

### product

The product.

### customer

The customer. To get prices for an unauthorized user, use a **0** value.

### website

The website.

### unit

The unit of quantity.

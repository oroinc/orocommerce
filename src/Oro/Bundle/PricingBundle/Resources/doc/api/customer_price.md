# Oro\Bundle\PricingBundle\Api\Model\CustomerPrice

## ACTIONS

### get_list

Retrieve a collection of product price records by the scope criteria (customerId, websiteId, productIds, etc.).

{@inheritdoc}

**Note:** It is required to provide the **customer**, **product**, **website** filters with request.

## FIELDS

### id

The unique identifier of a resource is created by the pattern (customer-website-product-currency-unit-quantity).

### currency

The product price currency.

### quantity

The product quantity.

### value

The product price.

### product

The product of a product price.

### customer

The customer related to a product price.

### website

The website related to a product price.

### unit

The unit of a product.

# Oro\Bundle\ProductBundle\Entity\Product

## FIELDS

### prices

An array of product prices.

Each element of the array is an object with the following properties:

**price** is a string contains the monetary value of the price.

**currencyId** is a string contains the currency of the price value.

**quantity** is a number contains the product quantity the price is applicable to.

**unit** is a string contains the ID of the product unit.

Example of data: **\[{"price": "1.23", "currency": "USD", "quantity": 1, "unit": "set"}\]**

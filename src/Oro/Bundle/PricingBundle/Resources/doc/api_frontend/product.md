# Oro\Bundle\ProductBundle\Entity\Product

## FIELDS

### prices

An array of product prices.

Each element of the array is an object with the following properties:

**price** is a string that contains the monetary value of the price.

**currencyId** is a string that contains the currency of the price value.

**quantity** is a number that contains the product quantity the price is applicable to.

**unit** is a string that contains the ID of the product unit.

Example of data: **\[{"price": "1.23", "currencyId": "USD", "quantity": 1, "unit": "set"}\]**

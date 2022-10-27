OroFixedProductShippingBundle
=============================

OroFixedProductShippingBundle provides default shipping integration for the OroCommerce applications.

Entity
------
Added new 'shippingCost' product attribute for Product entity.

Configuration
-------------
Added 'Fixed Product Shipping Cost' integration with next fields:
* type
* name
* label (This label displayed during checkout on the storefront)
* status
* default owner

Added 'Fixed Product Shipping' shipping rule with next options:
* Surcharge Type - can be 'Percent' or 'Fixed Amount'
* Surcharge On - can be 'Product Shipping Cost' or 'Product Price'
* Surcharge Amount - can be percentage value, or a fixed amount

Calculation rules
-----------------
Shipping amount calculation depends on shipping configuration options.
Product Shipping Cost - is a 'shippingCost' product attribute.
There are the next rules:

1. If Surcharge Type == 'Fixed Amount':
> Shipping Price = Product Shipping Cost + Surcharge Amount
2. If Surcharge Type == 'Percent' and Surcharge On == 'Product Price':
> Shipping Price = Product Shipping Cost + Product Price * Surcharge Amount / 100
3. If Surcharge Type == 'Percent' and Surcharge On == 'Product Shipping Cost':
> Shipping Price = Product Shipping Cost + Product Shipping Cost * Surcharge Amount / 100

Sample data
-----------
'Fixed Product Shipping' shipping integration was added as default.
Also, for 1/3 of the products from 1 to 21 id was added default value for 'shippingCost' field.

Order confirmation email template
=================

This template includes all Order information details such as shipping and billing addresses, payment details, order date, all available data for each line item, etc.
To access this information user can use variables, TWIG functions and filters when editing order confirmation email template.

`Order confirmation email tempate` use such variables as:
- `entity` - Order instance.

In the `Order confirmation email tempate` can be used twig:
- [Functions](#functions)
- [Filters](#filters)

**Note:** Please, refer to the [Email Templates](https://doc.oroinc.com/user/back-office/system/emails/email-templates/#view-available-template-variables-and-functions)
article of the OroCommerce documentation for the full list of available functions, filters and tags.

Functions
-------

- `oro_order_shipping_method_label`, provides a label of a shipping method based on information about shipping method and shipping method type
- `get_payment_methods`, provides information about payment methods based on the instance of Order
- `get_payment_term`, provides payment term based on the instance of Order
- `get_payment_status_label`, provides a formatted label of a payment status based on the value, that could be retrieved by function `get_payment_status`
- `get_payment_status`, provides an internal value of the payment status based on the instance of Order
- `order_line_items`, provides Order Line Items data based on the instance of Order
- `line_items_discounts`, provides Line Items discounts information based on the instance of Order

Filters
-------

- `oro_format_name`, returns a text representation of the given object
- `oro_format_date`, return a text representation of the date according to locale settings
- `oro_format_address`, formats address according to locale settings
- `oro_format_short_product_unit_value`, formats product unit value based on the given product unit. [Examples of usage](../../../../ProductBundle/Resources/doc/product-unit-value-formatting.md)
- `oro_format_price`, formats currency number according to locale settings
- `oro_format_currency`, formats currency number according to localized format

Notes
-------

There is `Oro\Bundle\CheckoutBundle\Migrations\Data\ORM\UpdateOrderConfirmationEmailTemplate` migration which modifies old template to the new one
but only if there was no any user customizations in it.

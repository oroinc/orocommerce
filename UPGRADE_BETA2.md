Upgrade from beta.1
=========================

CheckoutBundle:
---------------
- `AbstractCheckoutEntityListener` moved to namespace `OroB2B\Bundle\CheckoutBundle\EventListener`
- Creation of default checkout entity moved from `StartCheckout` to `CheckoutEntityListener`

WebsiteBundle:
--------------
- Added translation strategy to handle translation fallbacks on frontend based on locale structure from `OroB2BWebsiteBundle`

Layouts:
--------
Layout block types was replaced with DI only configuration for listed block types:
`category_list`, `checkout_transition_back`, `checkout_transition_continue`, `checkout_transition_step_edit`, `address`, `currency`, `date`, `order_total`, `shopping_list_selector`, `tax`.

Corresponding block type classes were removed:
- `OroB2B/Bundle/CatalogBundle/Layout/Block/Type/CategoryListType`
- `OroB2B/Bundle/CheckoutBundle/Layout/Block/Type/TransitionButtonType`
- `OroB2B/Bundle/OrderBundle/Layout/Block/Type/AddressType`
- `OroB2B/Bundle/OrderBundle/Layout/Block/Type/CurrencyType`
- `OroB2B/Bundle/OrderBundle/Layout/Block/Type/DateType`
- `OroB2B/Bundle/OrderBundle/Layout/Block/Type/OrderTotalType`
- `OroB2B/Bundle/ShoppingListBundle/Layout/Block/Type/ShoppingListSelectorType`
- `OroB2B/Bundle/TaxBundle/Layout/Block/Type/TaxType`

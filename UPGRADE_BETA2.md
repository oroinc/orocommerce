Upgrade from beta.1
=========================

CheckoutBundle:
---------------
- `AbstractCheckoutEntityListener` moved to namespace `OroB2B\Bundle\CheckoutBundle\EventListener`
- Creation of default checkout entity moved from `StartCheckout` to `CheckoutEntityListener`

WebsiteBundle:
--------------
- Added translation strategy to handle translation fallbacks on frontend based on locale structure from `OroB2BWebsiteBundle`

ProductBundle:
--------------
- Added `OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider`, `OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitsType` in order to populate all available Product Units in System Configuration section
- Added `OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider`, Modified `OroB2B\Bundle\ProductBundle\Form\Type\ProductType` in order to fill Product Units with values from System Configuration on product creation page

MoneyOrderBundle:
--------------
- Added bundle that adds 'Check / Money Order' payment method with power of OroB2B PaymentBundle

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

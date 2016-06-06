Upgrade from beta.1
=========================

CheckoutBundle:
---------------
- `AbstractCheckoutEntityListener` moved to namespace `OroB2B\Bundle\CheckoutBundle\EventListener`
- Creation of default checkout entity moved from `StartCheckout` to `CheckoutEntityListener`

WebsiteBundle:
--------------
- Added translation strategy to handle translation fallbacks on frontend based on locale structure from `OroB2BWebsiteBundle`

PricingBundle:
--------------
- Modified `OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceUnitSelectorType` in order to work with PrimaryUnitPrecision and AdditionalUnitPrecisions logic
- Modified `product-unit-precision-limitations-component` in order to work with PrimaryUnitPrecision and AdditionalUnitPrecisions logic

ProductBundle:
--------------
- Added `OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider`, `OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitsType` in order to populate all available Product Units in System Configuration section
- Added `OroB2B\Bundle\ProductBundle\Provider\DefaultProductUnitProvider`, Modified `OroB2B\Bundle\ProductBundle\Form\Type\ProductType` in order to fill Product Units with values from System Configuration on product creation page
- Added `OroB2B\Bundle\ProductBundle\Form\Type\ProductPimaryUnitPrecisionType`, Modified `OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType` in order to fill ProductPrimaryUnitPrecision and ProductAdditionalUnitPrecisions respectively
- Modified `OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType` with option `sell`
- Modified `OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision` with properties: `conversionRate`, `sell`. Schema and Demo Data Migrations changed also.
- Modified `OroB2B\Bundle\ProductBundle\Entity\Product` with property primaryUnitPrecision as one-to-one relation to `OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision`
- Modified `OroB2B\Bundle\ProductBundle\Entity\Product` with methods in order to get, add, remove additionalUnitPrecisions collection
- Modified ImportExport with ProductPrimaryUnitPrecision and ProductAdditionalUnitPrecisions logic
- Added `product-primary-unit-limitations-component`, Modified `product-unit-selection-limitations-component` in order to work with PrimaryUnitPrecision and AdditionalUnitPrecisions on Product create/update page

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

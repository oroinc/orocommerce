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
- Replaced single product image with typed product image collection

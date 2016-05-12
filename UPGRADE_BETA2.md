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
- Replaced single product image with typed product image collection

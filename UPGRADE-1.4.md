UPGRADE FROM 1.3 to 1.4
=======================

**IMPORTANT**
-------------

Some inline underscore templates from next bundles, were moved to separate .html file for each template:
 - PricingBundle
 - ProductBundle
 
PaymentBundle
-------------
- Event `oro_payment.require_payment_redirect.PAYMENT_METHOD_IDENTIFIER` is no more specifically dispatched for each
payment method. Use generic `oro_payment.require_payment_redirect` event instead.


# Checkout Subtotal

The data from the Subtotal column (sum of all checkout items) is stored in the database. It is required for efficient assembly of the open orders (checkouts) datagrid. The data gets updated after the following actions:

* Once a product price list is changed, it triggers the subtotal recalculation for all checkouts with such product included. 

The list of events:
    * `Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent`(`oro_pricing.combined_price_list.update`)
    * `Oro\Bundle\PricingBundle\Event\CustomerGroupCPLUpdateEvent`(`oro_pricing.customer_group.combined_price_list.update`)
    * `Oro\Bundle\PricingBundle\Event\CustomerCPLUpdateEvent`(`oro_pricing.customer.combined_price_list.update`)
    * `Oro\Bundle\PricingBundle\Event\WebsiteCPLUpdateEvent`(`oro_pricing.website.combined_price_list.update`)
    * `Oro\Bundle\PricingBundle\Event\ConfigCPLUpdateEvent`(`oro_pricing.config.combined_price_list.update`) 
    
* Subtotals are also recalculated within the HTTP-request on each step of the checkout process.

* In the open orders datagrid, subtotals are recalculated once the datagrid information request is received, but only if the related product price list was changed, and subtotal was not updated in the message queue.

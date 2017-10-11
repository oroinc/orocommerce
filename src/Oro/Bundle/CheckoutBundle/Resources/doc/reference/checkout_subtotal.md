Checkout Subtotal
=================

The data for column Subtotal (sum of amounts of all checkout items) now are stored in Database, it's required for efficient assembly of Datagrid for Open Orders (Checkouts). The data are updated after following actions:
* On Product(s) Price List changed - background recalculation be ran for all Checkouts with given Product(s). 
The list of events:
    * `Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent`(`oro_pricing.combined_price_list.update`); 
    * `Oro\Bundle\PricingBundle\Event\CustomerGroupCPLUpdateEvent`(`oro_pricing.customer_group.combined_price_list.update`); 
    * `Oro\Bundle\PricingBundle\Event\CustomerCPLUpdateEvent`(`oro_pricing.customer.combined_price_list.update`); 
    * `Oro\Bundle\PricingBundle\Event\WebsiteCPLUpdateEvent`(`oro_pricing.website.combined_price_list.update`); 
    * `Oro\Bundle\PricingBundle\Event\ConfigCPLUpdateEvent`(`oro_pricing.config.combined_price_list.update`) ;
* On Start or Continue Checkout - Subtotals are recalculated within current request(HTTP-Request) for current checkout; 
* For Datagrid "Open Orders", the Subtotals are recalculated during datagrid data request,
but only if related Price List for Product was changed and subtotal was not updated in the message queue. 

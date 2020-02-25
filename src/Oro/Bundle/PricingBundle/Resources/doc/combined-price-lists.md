# Combined Price List

Combined Price List (CPL) is an internal entity which stored prices shown to the end customer.
Each CPL represents some Price Lists chain. Such chain is created based on the fallback settings 
and price lists assigned to one of the levels: config, website, customer group, customer.

Each chain is merged by pricing merge strategy according to it`s logic.

## Building logic

As mentioned before prices may be assigned to different levels. To form correct price lists chain
each level is served by own CombinedPriceListsBuilder. There are 4 different builders:

 - CombinedPriceListsBuilder - Performs CPL build for config level, 
 calls website CPL builder for websites with fallback to config.

 - WebsiteCombinedPriceListsBuilder - Updates or creates CPLs for website scope, 
 calls customer group CPL builder for groups with fallback to website

 - CustomerGroupCombinedPriceListsBuilder - Updates or creates combined price lists for customer group scope. 
 Performs CPL build for customer group level, calls customer CPL builder for customers with fallback to customer group,
 calls customer CPL builder for customers with fallback to customer group and with empty group when concrete customer
 group not passed as $currentCustomerGroup parameter (build for all groups)
 
 - CustomerCombinedPriceListsBuilder - Updates or creates combined price lists for customer scope

All these builders shouldn't be used directly and may be accessed only by CombinedPriceListsBuilderFacade.
To rebuild combined price lists on a given level CombinedPriceListsBuilderFacade should be used.
CombinedPriceListsBuilderFacade provides a clean interface for rebuilding combined price lists,
 dispatches required events when CPLs are updated and calls CombinedPriceListGarbageCollector which removes all unused CPLs. 

 - rebuild - executes rebuild of a given CPL with optional products to rebuild passed
 - rebuildAll - rebuilds all CPLs. First execute rebuild of config level which will cascade call all underlying builders
 for entities with fallback to previous level. config -> website -> customer group -> customer. 
 After processing of entities with default fallback entities with "current level only" fallback are processed one by one.
 Note that each level except the last one will cascade call all underlying builders for entities with fallback to previous level.
 - rebuildForWebsites - calls WebsiteCombinedPriceListsBuilder for given websites
 - rebuildForCustomerGroups - calls CustomerGroupCombinedPriceListsBuilder for given customer groups
 - rebuildForCustomers - calls CustomerCombinedPriceListsBuilder for given customers
 - rebuildForPriceLists - collects entities from each level which contains given price lists in the chain 
 and executes CPL rebuild
 
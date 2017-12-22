Actions
==========

* [Overview](#overview)
* [Product specific components](#product-specific-components)
    * [Actions](#actions)
    * [Conditions](#conditions)

Overview
--------
ActionBundle provide a way to bring complex solutions in ORO based projects with reusable blocks of configuration,
which include Buttons, Operations and Actions Groups. You can read more about specific components in 
ActionBundle (platform).

Product specific components
-------

### Actions

#### Remove Restricted Products

**Class:** Oro\Bundle\ProductBundle\Action\RemoveRestrictedProducts

**Alias:** remove_restricted_products

**Description:** 
Remove restricted (disabled, hidden, etc.) products from given source.
Optionally also saves removed products in provided attribute

**Parameters:**
 - productHolderPath - array of values that implement `ProductHolderInterface`
 - attribute - attribute where removed products should be saved to; *optional*

**Configuration Example**
```
- '@remove_restricted_products':
    attribute: $.removedProducts
    productHolderPath: $.data.productLineItems
```

### Conditions

#### At Least One Available Products

**Class:** Oro\Bundle\ProductBundle\Action\Condition\AtLeastOneAvailableProduct

**Alias:** at_least_one_available_product

**Description:** This condition checks if there is at least one available product in provided source

**Parameters:** array of values that implement `ProductHolderInterface`

**Configuration Example**
```
preconditions:
    '@and':
        - '@at_least_one_available_product': $.data.productLineItems
```

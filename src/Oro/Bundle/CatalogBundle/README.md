Oro\Bundle\CatalogBundle\OroCatalogBundle
===============================================

Table of Contents
-----------------
 - [Description](#description)
 - Category creation
    - [Default Product Options](./Resources/doc/default-product-options.md)
 - [Breadcrumbs](#breadcrumbs)

Description
------------

The OroCatalogBundle introduces the notion of categories, which are using for products structuring in the system. This bundle provides an UI for category management.


Breadcrumbs
------------

Default breadcrumbs behavoiur of OroCatalogBundle is building them based on the category tree.

**Example:**

With this simple category tree for ```category-1-1-1``` 
```
    - category-1
        - category-1-1
            - category-1-1-1
        - category-1-2
    - category-2
    - category-3
```
**Will look like:**
```
Products categories \ category-1 \ category-1-1 \ category-1-1-1
```
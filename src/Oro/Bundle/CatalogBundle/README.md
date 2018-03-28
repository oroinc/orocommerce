# Oro\Bundle\CatalogBundle\OroCatalogBundle

# Table of Contents

 - [Description](#description)
 - Category creation
    - [Default Product Options](./Resources/doc/default-product-options.md)
 - [Breadcrumbs](#breadcrumbs)

# Description

OroCatalogBundle defines master catalog and categories that aimed to structure products set in the OroCommerce applications. The bundle provides the ability to manage categories and assign products to them.

# Breadcrumbs

Default breadcrumbs of OroCatalogBundle are built based on the category tree.

**Example:**

Following the example below, a simple category tree for ```category-1-1-1``` 
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
All Products \ category-1 \ category-1-1 \ category-1-1-1
```

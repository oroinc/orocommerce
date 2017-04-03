Oro\Bundle\WebCatalogBundle\OroWebCatalogBundle
===============================================

Table of Contents
-----------------
 - [Description](#description)
 - [Breadcrumbs](#breadcrumbs)

Description
------------

The OroWebCatalogBundle introduces ability to manage multiple WebCatalogs from the UI.


Breadcrumbs
------------

With OroWebCatalogBundle you can override the default breadcrumbs data source.

After creating and enabling new WebCatalog in a website, 
the breadcrumbs will be rendered in sync with the user-defined WebCatalog tree structure.


**Example:**

Let's have an simple category tree like below: 
```
- WebCatalog <name of the webcatalog>
   - Lighting Products
        - Architectural Floodlighting
        - Headlamps
   - Medical Apparel
        - Medical Uniforms
   - Office Furniture
   - Retail Supplies
        - POS Systems
        - Printers
```
When navigating to ```Medical Uniforms```, the breadcrumbs
will like look following:
```
WebCatalog <name of the webcatalog> \ Medical Apparel \ Medical Uniforms
```

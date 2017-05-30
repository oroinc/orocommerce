SEO meta fields
===============

Table of Contents
-----------------
 - [Description](#responsibilities)
 - [Technical details](#technical-details)

Description
-----------
The OroSEOBundle adds functionality both on the management console and on the front store. This is done through extension of existing entities from the platform by adding new SEO section on view/edit pages from admin side and adding meta tags in the html code of the configured pages.
The following entities and their corresponding front store pages have been extended with this the SEO functionality:
- Product (OroProductBundle) with admin view and edit
- Category (OroCatalogBundle) with admin edit
- LandingPage (OroCMSBundle) with admin view and edit
- ContentNode (OroWebCatalogBundle) with admin view and edit

In the management console, for extended entity (e.g. Product, Category, LandingPage, or ContentNode) view and edit pages, the new SEO section with the SEO fields title, description and keywords was added. These SEO options apply to the currently viewed entity and may be modified for all locales.

On the front store, the SEO fields with their values in the HTML of website pages for the search engines to pick them up.

Technical details
-----------------
The Product, Category, LandingPage and ContentNode entities are extended form extensions with three new fields that are stored as collections of LocalizedFallbackValue.
Therefore these new fields added through extend extension are many-to-many relations between the specified entities and LocalizedFallbackValue entity.

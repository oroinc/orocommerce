# Oro\Bundle\ProductBundle\Api\Model\ProductCollection

## ACTIONS

### get

Retrieve a product collection for the web catalog tree node content.

This API resource uses high-performance search queries based on indexed products information.
The detailed information about **searchQuery**, **aggregations** and **sort** filters can be found
in the documentation for **productsearch** API resource.
These filters are used to filter and sorting data for the **products** relationship.

## FIELDS

### products

The list of products found by the search query.

## FILTERS

### searchQuery

The filter that is used to specify the search query for 'products' relationship.

### aggregations

The filter that is used to request aggregated data for 'products' relationship.

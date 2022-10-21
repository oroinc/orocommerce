# Oro\Bundle\ProductBundle\Entity\Product

## FIELDS

### category

The master catalog category.

### category_sort_order

The sort order of the product in the master catalog category.

## FILTERS

### rootCategory

Filter products by child master catalog categories for a category with the specified ID, independing on the nesting level of them. Use 'gt' operator to filter by child categories without the specified category. Use 'gte' operator to filter by the specified category and its child categories.

## SUBRESOURCES

### category

#### get_subresource

Retrieve a record of the master catalog category that a specific product belongs to.

#### get_relationship

Retrieve the ID of the master catalog category that a specific product belongs to.

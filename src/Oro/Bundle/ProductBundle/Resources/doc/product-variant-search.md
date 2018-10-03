# Product Variant Search

## General Information

This document describes the way the search index behaves while a user is searching for a configurable product or a product variant and founds the configurable product by the values from the assigned product variants. Also here you can find what data
is stored at the search index and how to customize it.

## How It Works

When a user creates a configurable product, they associate it with several simple products, called product variants. These variants should have different values for the configurable product attribute so that the application can identify an appropriate product variant by the value of the configurable product attribute.

When a user performs a search in the application storefront, they should be able to find a configurable product by the values from
the associated product variants. This feature works for the global search (all text), select and multi-select fields.

For example, there are three simple products with the TAG1, TAG2, and TAG3 SKUs, and one configurable product with the SKU set to GENERAL-TAG. Also, there are two attributes, Color that has the select type and Material that has the multi-select type. Both attributes are searchable and filterable. Color is used as a configurable product attribute. Here is the data we have for these products:

```
Product SKU: TAG1
Color: Red
Material: Paper, Plastic

Product SKU: TAG2
Color: Green
Material: Paper

Product SKU: TAG3
Color: Blue
Material: Plastic

Product SKU: GENERAL-TAG
Color: empty
Material: empty
``` 

There can be two scenarios, when product variants are invisible in the storefront (this is a default behavior), and when they are visible (it has to be set manually in the system configuration).

First, let's check the application behavior when product variants are invisible.

| Filter       | Value       | Found products |
|--------------|-------------|----------------|
| All text     | TAG1        | GENERAL-TAG    |
| All text     | TAG2        | GENERAL-TAG    |
| All text     | TAG3        | GENERAL-TAG    |
| All text     | TAG4        |                |
| All text     | GENERAL-TAG | GENERAL-TAG    |
| Color        | Red         | GENERAL-TAG    |
| Color        | Green       | GENERAL-TAG    |
| Color        | Blue        | GENERAL-TAG    |
| Color        | White       |                |
| All text     | Red         | GENERAL-TAG    |
| All text     | Green       | GENERAL-TAG    |
| All text     | Blue        | GENERAL-TAG    |
| All text     | White       |                |
| Material     | Paper       | GENERAL-TAG    |
| Material     | Plastic     | GENERAL-TAG    |
| Material     | Metal       |                |
| All text     | Paper       | GENERAL-TAG    |
| All text     | Plastic     | GENERAL-TAG    |
| All text     | Metal       |                |

As we can see, a configurable product can be found by the values both from the configurable product and the associated product variants.

Now let's check the application behavior when product variants are visible.

| Filter       | Value       | Found products          |
|--------------|-------------|-------------------------|
| All text     | TAG1        | GENERAL-TAG, TAG1       |
| All text     | TAG2        | GENERAL-TAG, TAG2       |
| All text     | TAG3        | GENERAL-TAG, TAG3       |
| All text     | TAG4        |                         |
| All text     | GENERAL-TAG | GENERAL-TAG             |
| Color        | Red         | GENERAL-TAG, TAG1       |
| Color        | Green       | GENERAL-TAG, TAG2       |
| Color        | Blue        | GENERAL-TAG, TAG3       |
| Color        | White       |                         |
| All text     | Red         | GENERAL-TAG, TAG1       |
| All text     | Green       | GENERAL-TAG, TAG2       |
| All text     | Blue        | GENERAL-TAG, TAG3       |
| All text     | White       |                         |
| Material     | Paper       | GENERAL-TAG, TAG1, TAG2 |
| Material     | Plastic     | GENERAL-TAG, TAG1, TAG3 |
| Material     | Metal       |                         |
| All text     | Paper       | GENERAL-TAG, TAG1, TAG2 |
| All text     | Plastic     | GENERAL-TAG, TAG1, TAG3 |
| All text     | Metal       |                         |

As we can see, both the configurable product and product variants can be found using the values from the
product variants.

Pay attention that the configurable product can be found not only by the configurable attribute filter 
(e.g. Color = Green), but also by the text representation of the appropriate option (e.g. All text = Green). 
 

## Search Index Data

Once you understand the expected behavior, you can check the search index data to find out how the application implements this behavior. 

Let's use the same example with the tag products from the previous section and check what data we have at the search index.

*TAG1*
```json
{
    "sku" : "TAG1",
    "is_variant" : "1",
    "all_text_1" : "TAG1 Red Paper Plastic",
    "color_red" : "1",
    "material_paper" : "1",
    "material_plastic" : "1"
}
``` 

*TAG2*
```json
{
    "sku" : "TAG2",
    "is_variant" : "1",
    "all_text_1" : "TAG2 Green Paper",
    "color_green" : "1",
    "material_paper" : "1"
}
```

*TAG3*
```json
{
    "sku" : "TAG3",
    "is_variant" : "1",
    "all_text_1" : "TAG3 Blue Plastic",
    "color_blue" : "1",
    "material_plastic" : "1"
}
```

*GENERAL-TAG*
```json
{
    "sku" : "GENERAL-TAG",
    "is_variant" : "0",
    "all_text_1" : "GENERAL-TAG TAG1 TAG2 TAG3 Red Green Blue Paper Plastic",
    "color_red" : "1",
    "color_green" : "1",
    "color_blue" : "1",
    "material_paper" : "1",
    "material_plastic" : "1"
}
```

From the example, we see that configurable product includes text, select, and multi-select attribute values from the
product variants. So, when the application executes search query with the `all_text_1 ~ TAG1` or `color_red = 1` restriction, both the configurable product and the product variant are found. 


## Extension Points

The logic that adds product variant data to the configurable product is encapsulated at 
`Oro\Bundle\ProductBundle\Search\ProductVariantProviderDecorator` class. This class decorates the `Oro\Bundle\ProductBundle\Search\WebsiteSearchProductIndexDataProvider` standard data provider and adds the text, select, and multi-select attribute values of a product variant to the configurable product.

If you need to change this behavior or change the logic of data collection, you need to create another data provider decorator that implements the `Oro\Bundle\ProductBundle\Search\ProductIndexDataProviderInterface` interface which changes the search index data. Then you need to decorate the original provider in the DI container.
Below is the example of how you can implement it:

```yml
services:
    oro_product.provider.website_search_index_data.product_variants:
        class: 'Oro\Bundle\ProductBundle\Search\ProductVariantProviderDecorator'
        decorates: 'oro_product.provider.website_search_index_data'
        decoration_inner_name: 'oro_product.provider.website_search_index_data.original'
        arguments:
            - '@oro_product.provider.website_search_index_data.original'
```

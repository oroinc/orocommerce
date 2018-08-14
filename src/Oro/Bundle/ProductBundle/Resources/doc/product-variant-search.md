Product Variant Search
======================

### General Information

This document describes how search index behaves when configurable product or product variant are searched and when
configurable product can be found by the values from the assigned product variants. Also here you can find what data
is stored at the search index and how to customize it.


### How does it work?

Every time user creates a configurable product he usually associates it with a several simple products called product
variants. These variants have to have different values for the configurable product attribute, so application should be
able to identify an appropriate product variant by the value of the configurable product attribute.

When user performs search at application front-office he has to be able to find configurable product by the values from
the associated product variants. This feature works for global search (all text), select and multi-select fields.

Let's have a look at the example. Imagine that we have three simple products with SKUs like TAG1, TAG2 and TAG3; 
and then we have one configurable product with SKU set to GENERAL-TAG. Also we have two attributes - 
Color which has select type and Material which has multi-select type; both attributes are searchable and filterable. 
Color is used as configurable product attribute. Here is the data we have for these products:

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

We have two scenarios - when product variants are invisible at the front office (this is a default behaviour) and 
when they are visible (it has to be set manually at the system configuration).

First let's check application behaviour when product variants are invisible.

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

As we can see configurable product can be found by the values both from configurable product and
associated product variants.

Not let's check application behaviour when product variants are visible.

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

As we can see both configurable product and product variants can be found using the values from the
product variants.

Pay attention that configurable product can be found not only by configurable attribute filter 
(e.g. Color = Green), but also by the text representation of the appropriate option (i.e. All text = Green). 
 

### Search index data

After the expected behavior is absolutely clear we can check search index data to understand how application
implements this behaviour. 

Let's use the same example with tag products from the previous chapter and check what data we have at
the search index.

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

It's clearly visible that configurable product includes text, select and multi-select attribute values from the
product variants. So, when application executes search query with restriction `all_text_1 ~ TAG1` or `color_red = 1` 
both configurable product and product variant are found. 


### Extension points

The logic that adds product variant data to the configurable product is encapsulated at 
`Oro\Bundle\ProductBundle\Search\ProductVariantProviderDecorator` class. This class decorates standard data provider 
`Oro\Bundle\ProductBundle\Search\WebsiteSearchProductIndexDataProvider` and adds text, select and multi-select 
attribute values of product variant to the configurable product.

If you need to change this behaviour or change logic of data collection is general you may create another data
provider decorator that implements interface `Oro\Bundle\ProductBundle\Search\ProductIndexDataProviderInterface` which 
changes the search index data. Then you need to decorate original provider at DI container - 
here is an example of how it can be done:

```yml
services:
    oro_product.provider.website_search_index_data.product_variants:
        class: 'Oro\Bundle\ProductBundle\Search\ProductVariantProviderDecorator'
        decorates: 'oro_product.provider.website_search_index_data'
        decoration_inner_name: 'oro_product.provider.website_search_index_data.original'
        arguments:
            - '@oro_product.provider.website_search_index_data.original'
```

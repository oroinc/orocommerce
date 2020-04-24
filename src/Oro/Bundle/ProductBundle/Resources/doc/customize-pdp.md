Customize Product View Page
===========================

In this example we look at how to customize product with different product types:

- [Simple Product](#simple-product)
- [Configurable Product](#configurable-product)
- [Change Product Page by Category](#change-product-page-by-category)
- [Product Page Templates](#product-page-templates)

### Getting Started

Every product view page contains the current **product_type** in the layout context. You can use it in your layout update **conditions**. When you customize any page, remember to use **Symfony Profiler** and look into the **Layout** section, where the current layout **context** data and actual layout **tree** can be found. 

Please see the [Debug Information](https://github.com/orocrm/platform/tree/master/src/Oro/Bundle/LayoutBundle/Resources/doc/debug_information.md) section for more details.

First, create an **import** and a **template** that will be used in a **Simple Product** and a **Configurable Product**.

Import:

```yml
# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/imports/oro_product_view/layout.yml

layout:
    actions:
        - '@setBlockTheme':
            themes: 'AcmeProductBundle:layouts:default/imports/oro_product_view/layout.html.twig'

        - '@addTree':
            items:
                product_view_page:
                    blockType: container
                product_container:
                    blockType: product_view_container
                    options:
                        product: '=data["product"]'
                product_title_mobile:
                    blockType: text
                    options:
                        text: '=data["locale"].getLocalizedValue(data["product"].getNames())'
                product_image:
                    blockType: product_image
                    options:
                        product: '=data["product"]'
                product_description_container:
                    blockType: container
                product_title_container:
                    blockType: container
                product_specification_container:
                    blockType: container
                product_specification:
                    blockType: container
                product_specification_sku:
                    blockType: text
                    options:
                        text: '=data["product"].getSku()'
                product_description_content:
                    blockType: container
                product_description:
                    blockType: text
                    options:
                        text: '=data["locale"].getLocalizedValue(data["product"].getDescriptions())'
                        escape: false
            tree:
                page_content:
                    product_view_page:
                        product_container:
                            product_image: ~
                            product_description_container:
                                product_title_container: ~
                                product_specification_container:
                                    product_specification:
                                        product_specification_sku: ~
                                product_description_content:
                                    product_description: ~

        - '@setOption':
            id: title
            optionName: params
            optionValue:
                '%name%': '=data["locale"].getLocalizedValue(data["product"].getNames())'

        - '@setOption':
            id: page_title
            optionName: defaultValue
            optionValue: '=data["locale"].getLocalizedValue(data["product"].getNames())'

        - '@move':
            id: page_title
            parentId: product_title_container
```

Template:

```twig
{# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/imports/oro_product_view/layout.html.twig #}

{% block _product_container_widget %}
    {% set attr = layout_attr_defaults(attr, {
        'data-page-component-module': 'oroui/js/app/components/view-component',
        '~data-page-component-options': {
            view: 'oroproduct/js/app/views/base-product-view',
            modelAttr: product.jsonSerialize()
        },
        'data-layout': 'separate',
        '~class': ' product clearfix'
    }) %}

    <div {{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _page_title_widget %}
    <h3 class="product-title">{{ text }}</h3>
{% endblock %}

{% block _product_image_widget %}
    <div class="product-gallery-widget product-gallery-widget_vertical product-gallery-widget_l_floated">
        <div class="product-gallery product-gallery_vertical">
            <div class="product-gallery__image-holder">
                <div class="product-gallery__image-holder__carousel" data-product-gallery>
                    <div class="product-gallery__image-holder__container">
                        {% set productImage = product.imagesByType('main')|length > 0 ? product.imagesByType('main').first.image : null %}
                        <img src="{{ product_filtered_image(productImage, 'product_extra_large') }}"
                             alt="{{ product.names|localized_value }}"
                             width="378"
                             height="378"
                                {% if productImage and isDesktopVersion() %}
                                    data-zoom-image="{{ product_filtered_image(productImage, 'product_original') }}"
                                    {% set options = {
                                    widgetModule: 'jquery-elevatezoom',
                                    widgetName: 'elevateZoom',
                                    scrollZoom: true,
                                    zoomWindowWidth: 630,
                                    zoomWindowHeight: 376,
                                    borderSize: 1,
                                    borderColour: '#ebebeb',
                                    lensBorderColour: '#7d7d7d',
                                    lensColour: '#000',
                                    lensOpacity: 0.22
                                    }|json_encode() %}
                                    data-page-component-module="oroui/js/app/components/jquery-widget-component"
                                    data-page-component-options="{{ options }}"
                                {% endif %}
                        />
                    </div>
                    {% for productImage in product.imagesByType('additional') %}
                        <img src="{{ product_filtered_image(productImage.image, 'product_small') }}" width="82"  height="82"/>
                    {% endfor %}
                </div>
            </div>
        </div>
    </div>
{% endblock %}

{% block _product_description_container_widget %}
    <div class="product__description-container">
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _product_title_container_widget %}
    {% set attr = attr|merge({
        class: attr.class|default('') ~ " product__title-container"
    }) %}

    <div {{ block('block_attributes') }}>
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _product_specification_container_widget %}
    <div class="product__specification-container product__specification-container_l_floated">
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _product_specification_widget %}
    <div class="product__specification">
        {{ block_widget(block) }}
    </div>
{% endblock %}

{% block _product_specification_sku_widget %}
    <div>
        {{ 'oro.product.frontend.index.item'|trans }} <span class="red">{{ block_widget(block) }}</span>
    </div>
{% endblock %}

{% block _product_description_content_widget %}
    <div class="product__description-content product__description-content_l_floated">
        <div class="product__description">
            {{ block_widget(block) }}
        </div>
    </div>
{% endblock %}
```

### Simple Product

In our case a simple product inherits all import properties. 

Create a layout update that includes the **oro_product_view** import and has the **conditions** to check if the current product has a **simple** product type.

Our simple product has the following look:
![Simple Product example](./images/simple_product.png "Simple Product example")


```yml
# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/simple_product.yml

layout:
    imports:
        - oro_product_view

    actions: []

    conditions: 'context["product_type"] == "simple"'
```

### Configurable Product

Create a layout update that includes the **oro_product_view** import and has the **conditions** to check if the current product has a **configurable** product type.

Add the **product variants** block and the template.

Our configurable product has the following look:
![Configurable Product example](./images/configurable_product.png "Configurable Product example")

Product Variants Block:

```yml
# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/configurable_product.yml

layout:
    imports:
        - oro_product_view

    actions:
        - '@setBlockTheme':
            themes: 'AcmeProductBundle:layouts:default/oro_product_frontend_product_view/configurable_product.html.twig'

        - '@add':
            id: product_variants
            blockType: product_variants
            parentId: product_specification_container
            siblingId: product_specification
            options:
                variants: '=data["product_variants"].getVariants(data["product"])'

    conditions: 'context["product_type"] == "configurable"'
```

Template:

```twig
{# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/configurable_product.html.twig #}

{% block _product_variants_widget %}
    {% for variant in variants %}
        {% set key = variant.name|lower %}
        <div class="product__{{ key }}s">
            <label for="product_variants_{{ key }}">Select {{ variant.name }}</label>

            {% if key == 'color' %}
                {% for key, name in variant.elements %}
                    <span class="badge badge_sm product__colors__item color-item_{{ key }}">
                        <input type="checkbox" name="{{ key }}" alt="{{ name }}" />
                    </span>
                {% endfor %}
            {% else %}
                <select id="product_variants_{{ key }}" class="select select--size-s">
                    {% for key, name in variant.elements %}
                        <option value="{{ key }}">{{ name }}</option>
                    {% endfor %}
                </select>
            {% endif %}
        </div>
    {% endfor %}
{% endblock %}
```

#### Block Types

For this example we need to create a **product_variants** block type used in the **configurable product** layout update.

```yml
# src/Acme/Bundle/ProductBundle/Resources/config/block_types.yml

services:
...
    acme_product.layout.type.product_variants:
        parent: oro_layout.block_type.abstract_configurable_container
        calls:
            - [setOptionsConfig, [{variants: {required: true}}]]
            - [setName, ['product_variants']]
        tags:
             - { name: layout.block_type, alias: product_variants }
...
```

#### Data Providers

Also, we need to create a **product_variants** data provider used in the **configurable product** layout update.

```yml
# src/Acme/Bundle/ProductBundle/Resources/config/services.yml

services:
...
    acme_product.layout.data_provider.product_variants:
        class: Acme\Bundle\ProductBundle\Layout\DataProvider\ProductVariantsProvider
        tags:
            - { name: layout.data_provider, alias: product_variants }
...
```

The following is an example of the data provider:

```php
<?php
// src/Acme/Bundle/ProductBundle/Layout/DataProvider/ProductVariantsProvider.php

namespace Acme\Bundle\ProductBundle\Layout\DataProvider;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVariantsProvider
{
    /**
     * @param Product $product
     *
     * @return array
     */
    public function getVariants(Product $product)
    {
        $variants = [];
        $variantFields = $product->getVariantFields();
        foreach ($variantFields as $variantField) {
            $variants[strtolower($variantField)]['name'] = $variantField;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $variantLinks = $product->getVariantLinks();
        foreach ($variantLinks as $variantLink) {
            $childProduct = $variantLink->getProduct();
            foreach ($variants as $key => $variant) {
                /** @var AbstractEnumValue $enumValue */
                $enumValue = $propertyAccessor->getValue($childProduct, $key);
                $variants[$key]['elements'][$enumValue->getId()] = $enumValue->getName();
            }
        }

        return $variants;
    }
}
```

### Change Product Page by Category

Every product view page contains the current **category_id** and the **category_ids** in the layout context. You can use these values to evaluate the layout **conditions**. When you customize any page, remember to use **Symfony Profiler** and look into the **Layout** section, where the current layout **context** data and actual layout **tree** can be found.
 
 Please see the [Debug Information](https://github.com/orocrm/platform/tree/master/src/Oro/Bundle/LayoutBundle/Resources/doc/debug_information.md) section for more details.

#### Example 1 (by category ID)

We have a "Headlamps" category and we want to add some static html to all products in this category.

The condition is: **conditions: 'context["category_id"] == 4'**.

The result is:
![Change Product Page by Category example 1](./images/change_product_by_category_example_1.png "Change Product Page by Category example 1")

```yml
# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/headlamps.yml

layout:
    actions:
        - '@setBlockTheme':
            themes: 'AcmeProductBundle:layouts:default/oro_product_frontend_product_view/headlamps.html.twig'

        - '@add':
            id: product_sale_banner
            blockType: block
            parentId: product_view_main_container
            siblingId: ~
            prepend: false

    conditions: 'context["category_id"] == 4'
```

Template:

```twig
{# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/headlamps.html.twig #}

{% block _product_sale_banner_widget %}
    <div class="text-right">
            <img src="{{ asset('/bundles/oroproduct/default/images/headlamps.jpg') }}"/>
        </div>
        <br />
{% endblock %}
```

#### Example 2 (by parent category ID)

As an example, we want to add a sale banner to all products in first level category and their children. We have a category called "Furniture" with **id = 6**.

The condition is: **conditions: '6 in context["category_ids"]'**.

The result is:
![Change Product Page by Category example 2](./images/change_product_by_category_example_2.png "Change Product Page by Category example 2")

```yml
# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/furniture.yml

layout:
    actions:
        - '@setBlockTheme':
            themes: 'AcmeProductBundle:layouts:default/oro_product_frontend_product_view/furniture.html.twig'

        - '@add':
            id: product_sale_banner
            blockType: block
            parentId: page_content
            siblingId: ~
            prepend: true

    conditions: '6 in context["category_ids"]'
```

Template:

```twig
{# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/furniture.html.twig #}

{% block _product_sale_banner_widget %}
    <div class="text-center">
        <img src="{{ asset('/bundles/oroproduct/default/images/furniture_sale.jpg') }}"/>
    </div>
    <br />
{% endblock %}
```

### Product Family

#### Attribute Set (example 1)

@TODO

#### Attribute Set (example 2)

@TODO

#### Attribute Groups

@TODO

### Product Page Templates

Please see the [Page Templates](https://github.com/orocrm/platform/tree/master/src/Oro/Bundle/LayoutBundle/Resources/doc/config_definition.md#page-templates) section for more details.

You can modify the visual presentation of the product view page for every product, or choose a page template for all of them by default.

First, create a **config** for the **page_templates** in our **theme**.

```yml
# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/config/page_templates.yml

templates:
    -
        label: Custom page template
        description: Custom page template description
        route_name: oro_product_frontend_product_view
        key: custom
    -
        label: Parent Additional page template
        description: Additional page template description
        route_name: oro_product_frontend_product_view
        key: additional
titles:
    oro_product_frontend_product_view: Product Page
```

Next, add some **layout updates**:

```yml
# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/page_template/custom/layout.yml

layout:
    actions:
        - '@remove':
            id: product_view_attribute_group_images
        - '@move':
            id: product_view_specification_container
            parentId: product_view_aside_container

```

```yml
# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/page_template/additional/layout.yml

layout:
    actions:
        - '@setBlockTheme':
            themes: 'AcmeProductBundle:layouts:default/oro_product_frontend_product_view/page_template/additional/layout.html.twig'
        - '@add':
            id: product_view_banner
            blockType: block
            parentId: product_view_content_container

```

Add some **templates**:

```twig
{# src/Acme/Bundle/ProductBundle/Resources/views/layouts/default/oro_product_frontend_product_view/page_template/additional/layout.html.twig #}

{% block _product_view_banner_widget %}
    <div class="text-center">
        <img src="{{ asset('/bundles/oroproduct/default/images/flashlights.png') }}"/>
    </div>
    <br />
{% endblock %}

```

#### Global level

To apply a **custom page template** to all products, go to **System > Configuration > Commerce > Design > Theme**.

In the **Page Templates** section, choose **Custom page template** in the **Product Page** select. 

In the front store, here is what it will look like:
![Global Product View Page with Custom Page Template](./images/global_product_view_page_with_custom_page_template.png "Global Product View Page with Custom Page Template")

#### Entity level

To apply a **custom page template** to the selected products, go to **Products > Products**, find your product and click **edit**.

In the **Design** section, choose **Additional page template** in the **Page Template** select. 
In the front store, here is what it will look like:
![Entity Product View Page with Custom Page Template](./images/entity_product_view_page_with_custom_page_template.png "Entity Product View Page with Custom Page Template")

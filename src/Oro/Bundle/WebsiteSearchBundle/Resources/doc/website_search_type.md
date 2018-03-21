Website search types
====================

The main idea of website search type is to provide search for different entities on frontend store. Widget 
`website_search_type_buttons` is responsible for this. When you use the search input on the frontend store, the request 
goes to `\Oro\Bundle\WebsiteSearchBundle\Controller\Frontend\WebsiteSearchController` which will forward request 
to actual entity controller using the `searchType` parameter. If string parameter `searchType` is not specified, a default 
`searchType` will be used instead. This widget could be enabled or disabled by setting flag in UI Configuration.  

### How to define a new Website search type

Firstly, you need to create class that implements the `Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchTypeInterface` 
and implement logic for next methods: `getRoute`, `getRouteParameters` and `getLabel`. 

Example:

```php
class ProductWebsiteSearchTypeProvider implements WebsiteSearchTypeInterface
{
    protected const ROUTE = 'oro_product_frontend_product_index';

    /**
     * {@inheritdoc}
     */
    public function getRoute(string $searchString = ''): string
    {
        return self::ROUTE;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.product.frontend.website_search_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteParameters(string $searchString = ''): array
    {
        $urlParams = [];
        if ($searchString) {
            $urlParams['grid']['frontend-product-search-grid'] = http_build_query(
                [
                    AbstractFilterExtension::MINIFIED_FILTER_PARAM => [
                        'all_text' => ['value' => $searchString, 'type' => TextFilterType::TYPE_CONTAINS],
                    ],
                ]
            );
        }

        return $urlParams;
    }
}
```

- Method `getRoute` just returns the route name. This method accepts one string parameter: `$searchString`. Route must 
lead to an existing frontend controller action with existing frontend page.

- Method `getRouteParameters` should return parameters for route. In this example result relies on the string parameter
`$searchString`. If `$searchString` parameter is not empty, the result will be an array 
`['grid']['frontend-product-search-grid']` which contains string values like 
`f%5Ball_text%5D%5Bvalue%5D=search+string&f%5Ball_text%5D%5Btype%5D=1`, where `search+string` is the `$searchString` 
parameter value. After redirecting, to properly applied filters, your datagrid must contain the field that was specified 
in this method. Example:

```yaml
    filters:
        columns:
            all_text:
                type:      string
                data_name: all_text_LOCALIZATION_ID
                label:     oro.product.anything.label
                max_length: 255
                options:
                    operator_choices:
                       '@oro_search.utils.search_all_text->getOperatorChoices'
```

- Method `getLabel` is responsible for how `website_search_type_buttons` will display this search type on frontend.  


Next step is to register this class as a service and tag it. Here is example:

```yaml
    oro_product.website_search_type_provider.product:
        class: Oro\Bundle\ProductBundle\Provider\ProductWebsiteSearchTypeProvider
        public: false
        tags:
            - { name: oro_website_search.search_type, type: product, isDefault: true, order: 10 }
```

Tag supports following options:
- **name** `oro_website_search.search_type` - this name will be used for collecting and adding to 
`oro_website_search.search_type_chain_provider`. Option is required.
- **type** should be a unique ID. It is used in the `searchType` parameter to find the correct service. This option is
required.
- **isDefault** - This option defines which service will be used by default. There **must** be at least **one** service 
with this option, however, this parameter is optional and its behaviour depending on the **order** value.
- **order**  Defines the service loading order. **isDefault** could be manipulated by using the **order** option. All services
are sorted via this parameter and after sorting they are added to the chain in given order. Also this option is responsible
for button rendering order in `website_search_type_buttons`. This option is required.

After cache clear you can use this route `/website-search/?search=lamp&searchType=product` for manual testing
or using the frontend search. In this example URL parameter `search` is the query string and parameter `searchType` 
is one of the possible search types, defined above. As result page will be redirected to the route that was defined in 
your service, that is responsible for this search type. In this example, redirection will head to 
`oro_product_frontend_product_index`. If there is no searchType provided, the default service will cause redirection.

### How to change the default web search type

For changing the default web search type, you need to create new class, that implements the
`Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchTypeInterface` and declare this service with highest processing
order.

For example, you have tagged services with these orders:
```yaml
- { name: oro_website_search.search_type, type: test_1, order: 10 }
- { name: oro_website_search.search_type, type: test_2, isDefault: true, order: 20 }
- { name: oro_website_search.search_type, type: test_3, order: 30 }
```
The service with `type: test_2` has order 20. So creation of service with `order` greater, than `20` will override the 
default search type. Example:

```yaml
- { name: oro_website_search.search_type, type: test_4, isDefault: true, order: 50 }
```

As a result, when `WebsiteSearchController` handles the request without `searchType` or with empty one, for redirecting 
will be used route and parameters from service with `type: test_4` instead of `type: test_2`.

### How to add button widget to layout

For adding a block to the layout you need to add a visible block, like in example:

```yaml
    - '@add':
        id: website_search_type_buttons
        parentId: page_main_header
        blockType: website_search_type_buttons
        options:
            search: '=data["website_search_type_button_provider"].getSearchKeyword()'
```

And that's all. Button widget will appear in the layout tree. Please adjust settings to move it to the correct position.

Website search configuration
============================


### Bundle configuration

The system configuration of the website search bundle consists of the following options:

* **engine** (string, default value is `orm`) - name of the search engine that should be used in the application;
* **engine_parameters** (array, empty by default) - additional parameters that are required to configure the engine. The default ORM engine does not require any parameters as it shared the database with the Oro application.

Here is an example of configuration that should be put to `config.yml` to enable custom search engine:

```yaml
oro_website_search:
    engine: 'custom'
    engine_parameters:
        host: '127.0.0.1'
        port: 9999
        login: 'username'
        password: '12345'
```

Please, remember, that format of parameters might be different for different search engines, so implementation of each engine has to parse this section and extract required parameters manually (e.g. using DI compiler pass).


### Mapping configuration

To add an entity to a search engine, provide the following information in system configuration:

* entity alias - it is used in search engine queries to specify which entities have to be selected from the index.
* a list of entity fields that should be included in the search index, with their types. Supported types are `text`, `integer`, `decimal` and `datetime`. This configuration is used to define the storage structure for the search index, that is necessary for the search speed optimization.

You can use placeholders in mapping configuration (e.g. LOCALIZTION_ID in the example below). Placeholders are dynamically substituted with the values based on the current environment (e.g. current website, localization, language). Use placeholders to get the identifier of the related entities. 

The search query should use placeholders and the search index contains resolved values,  i.e. placeholders are already substituted with identifiers.

The mapping configuration must be defined in the `Resources/config/oro/website_search.yml`  in your bundle, or in the `app/Resources/<bundleName>/Resources/config/oro/website_search.yml`.

Here is an example of mapping configuration for the product bundle:

```yaml
Oro\Bundle\ProductBundle\Entity\Product:
    alias: oro_product_WEBSITE_ID
    fields:
        -
            name: sku
            type: text
        -
            name: name_LOCALIZATION_ID
            type: text
```

This example shows configuration for the `Oro\Bundle\ProductBundle\Entity\Product` entity that uses `oro_product_WEBSITE_ID` alias. This alias contains `WEBSITE_ID` placeholder that generates a separate scope in the storage for each website. The real alias that is stored looks like `oro_product_1` (for a website with ID=1).

In the field configuration, we've defined `sku` and `name_LOCALIZATION_ID` fields.
The `sku` is a plain text field that is stored for every entity.
The `name_LOCALIZATION_ID` is also a text value, but this value is localized
(i.e. differs for every language) and uses `LOCALIZATION_ID` placeholder to map the localized values in storage (e.g. `name_1` for localization[1],  and `name_2` for localization[2]).


### Engine configuration

WebsiteSearchBundle helps you build and plug in any search engine. The 
[ORM search engine](./orm_engine.md) is presented out of the box. It can be used as an example to build other engines. Each engine
can have its own configuration, and WebsiteSearchBundle provides an easy way to handle it.

As it already was mentioned, bundle provides two options to configure search engine - `engine` and `engine_parameters`.
`engine_parameters` is just an array with no specific format, so any configuration can be passed there - it might
include connection credentials, storage configuration, security settings and other engine specific parameters.

Bundle configuration options are converted to DI container parameters - `oro_website_search.engine` and
`oro_website_search.engine_parameters` respectively. These parameters can be injected into engine service to handle
search properly.

To automatically use appropriate engine according to specified bundle configuration all services related to this engine
has to be placed in the `Resources/config/oro/search_engine/<engine>.yml` file in one of the bundles. Note: `<engine>` is the name of the engine defined by option `engine` in bundle configuration. By default, the `orm`
engine is used, so Oro application automatically loads `Resources/config/oro/search_engine/orm.yml` files from all bundles.

The biggest advantage of this approach is transparency - every developer can implement new search engine and easily plug it into the application.

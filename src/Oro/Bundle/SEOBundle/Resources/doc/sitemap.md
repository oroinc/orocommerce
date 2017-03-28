Sitemap
=======

Table of Contents
-----------------
 - [Description](#description)
 - [Technical details](#technical-details)
    * [HOW To add new provider](#how-to-add-new-provider)
    * [HOW To customize sitemap provider logic](#how-to-customize-sitemap-provider-logic)

Description
-----------
OroSEOBundle provides automated generation of the **sitemap.xml** and **robots.txt** files based on the website contents.
According to the https://www.sitemaps.org/index.html:

 ```
Sitemaps are an easy way for webmasters to inform search engines about pages on their sites that are available for crawling. In its simplest form, a Sitemap is an XML file that lists URLs for a site along with additional metadata about each URL (when it was last updated, how often it usually changes, and how important it is, relative to other URLs in the site) so that search engines can more intelligently crawl the site.
```

For multi-language websites, OroSEOBundle provides a *hreflang* attribute on pages that have content in different languages.

On the management console, this bundle provides the following system configuration options:
* Select the domain url that is used in the sitemap. You may call for using either secure or insecure domain. Secure URLs are preferable for *sitemap.xml* file. 
* Frequency of page updates [changefreq](https://www.sitemaps.org/protocol.html#changefreqdef) and [priority](https://www.sitemaps.org/protocol.html#prioritydef) of the URL compared to other website URLs may be configured per an entity that is included in the sitemap (e.g. Product, Category, and CmsPage).

To change the frequency of the sitemap generation globally, update the **Changefreq** option in the *Default* section in the **System Configuration > Websites > Sitemap**. The sitemap cron definition will adjust automatically.

Technical details
-----------------
URLs for sitemaps are received from providers. Each provider must implement `UrlItemsProviderInterface` interface and be registered with `oro_seo.sitemap.url_items_provider` DI tag. Such providers will be gathered in UrlItemsProviderRegistry.
There are 4 providers registered out of the box:

* three instances of the UrlItemsProvider:

    - oro_seo.sitemap.provider.product_url_items_provider:
    - oro_seo.sitemap.provider.category_url_items_provider:
    - oro_seo.sitemap.provider.cms_page_url_items_provider:

* and one instance of the WebCatalogUrlItemsProvider:

    - oro_seo.sitemap.provider.webcatalog_url_items_provider

Use `UrlItemsProviderRegistry` to collect all providers.

### HOW To add new provider

To create a simple provider, create an instance of the `UrlItemsProvider` with appropriate values for `providerType` and `entityName` parameters in the *services.yml* file.

```yaml
    my_provider:
        class: Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemProvider
        parent: oro_seo.sitemap.provider.url_items_provider
        arguments:
            - 'my_provider'
            - AppBundle/Entity/MyEntity
        tags:
            - { name: oro_seo.sitemap.url_items_provider, alias: 'my_provider' }
```

The `oro_seo.event.restrict_sitemap_entity.my_provider` event is triggered during the query builder iteration.
Also `oro_seo.event.url_items_provider_start.my_provider` and `oro_seo.event.url_items_provider_end.my_provider` events make something changes in the beginning and at the end of the `UrlItemProvider::getUrlItems` processing. These events may be used to restrict or modify original query from third-party developers.

### HOW To customize sitemap provider logic

Your new provider should implement `UrlItemsProviderInterface`:

```php
    // src/AppBundle/Sitemap/Provider/WebCatalogUrlItemsProvider
    class MyProvider implements UrlItemsProviderInterface
    {
        ...
            /**
             * {@inheritdoc}
             */
            public function getUrlItems(WebsiteInterface $website)
            {
                //Return \Generator|Oro\Bundle\SEOBundle\Model\DTO\UrlItem[]
            }
        ...
    }
```
and should be register in `UrlItemsProviderRegistry` using `oro_seo.sitemap.url_items_provider` tag:

```yaml
    my_provider:
        class: AppBundle/Sitemap/Provider/WebCatalogUrlItemsProvider
        tags:
            - { name: oro_seo.sitemap.url_items_provider, alias: 'my_provider' }
```

### HOW To make provider availability depend on the web catalog

New feature `frontend_master_catalog` was created to detect if web catalog restrictions should be applied. This feature may be handy for restricting entities based on the web catalog assignment.

The provider that depends on this feature should also implement `FeatureToggleableInterface`, use `FeatureCheckerHolderTrait`:

```php
    // src/AppBundle/Sitemap/Provider/WebCatalogUrlItemsProvider
    class MyProvider implements UrlItemsProviderInterface, 
    {
        use FeatureCheckerHolderTrait;
        ...
            /**
             * {@inheritdoc}
             */
            public function getUrlItems(WebsiteInterface $website)
            {
                if (!$this->isFeaturesEnabled()) {
                    return;
                }
                //Return \Generator|Oro\Bundle\SEOBundle\Model\DTO\UrlItem[]
            }
        ...
    }
```

and should be tagged with `oro_featuretogle.feature` tag for `frontend_master_catalog` feature.

```yaml
    my_provider:
        class: AppBundle/Sitemap/Provider/WebCatalogUrlItemsProvider
        tags:
            - { name: oro_seo.sitemap.url_items_provider, alias: 'my_provider' }
            - { name: oro_featuretogle.feature, feature: frontend_master_catalog }
```
**SitemapDumper**

*SitemapDumper* creates sitemap files in the `web` folder of your Oro application and logs sitemap location into appropriate files depending on the storage type:

* XmlSitemapIndexStorage - for index sitemap file
* XmlSitemapUrlsStorage - for particular sitemap file

All sitemap files are compressed with gzip utility and have *.gz* file extension.

**Robots.txt file**

The *DumpRobotsTxtListener.php* listener creates the *Robots.txt* file automatically based on the generated sitemaps.

**Sitemap generation**
The `oro:cron:sitemap:generate` command generates sitemap files for all providers into the `web/sitemaps/actual/` folder and logs the sitemap location in the *robots.txt* file.
Sitemap generation is a deferred process that is executed using the `SitemapGenerationProcessor.php` queue processor. For time-based sitemap generation on the predefined schedule, Oro application is using cron jobs.
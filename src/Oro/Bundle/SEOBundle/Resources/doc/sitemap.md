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

In the management console, this bundle provides the following system configuration options:
* Select the domain url that is used in the sitemap. You may call for using either secure or insecure domain. Secure URLs are preferable for *sitemap.xml* file. 
* Frequency of page updates [changefreq](https://www.sitemaps.org/protocol.html#changefreqdef) and [priority](https://www.sitemaps.org/protocol.html#prioritydef) of the URL compared to other website URLs may be configured per an entity that is included in the sitemap (e.g. Product, Category, and CmsPage).

To change the frequency of the sitemap generation globally, update the **Changefreq** option in the *Default* section in the **System Configuration > Websites > Sitemap**. The sitemap cron definition will adjust automatically.

Technical details
-----------------
URLs for sitemaps are received from providers. Each provider must implement `UrlItemsProviderInterface` interface and be registered with `oro_seo.sitemap.url_items_provider` or `oro_seo.sitemap.website_access_denied_urls_provider` DI tag or both of them. Such providers will be gathered in UrlItemsProviderRegistry.
There are 5 providers registered out of the box:

* three instances of the `UrlItemsProvider`:

    - oro_seo.sitemap.provider.product_url_items_provider:
    - oro_seo.sitemap.provider.category_url_items_provider:
    - oro_seo.sitemap.provider.cms_page_url_items_provider:
    - oro_seo.sitemap.provider.router_sitemap_urls_provider

* and one instance of the `ContentVariantUrlItemsProvider`:

    - oro_seo.sitemap.provider.content_variant_items_provider

There is one more tag available to register URL providers: `oro_seo.sitemap.website_access_denied_urls_provider`
Providers with this tag will be used in case you restrict access to the website. 

To add custom logic to providers, each provider dispatches events on start and end of the `UrlItemsProvider::getUrlItems` method:
```php
    /**
     * {@inheritdoc}
     */
    public function getUrlItems(WebsiteInterface $website, $version)
    {
        $this->loadConfigs($website);

        $this->dispatchIterationEvent(UrlItemsProviderEvent::ON_START, $website, $version);

        foreach ($this->getResultIterator($website, $version) as $row) {
            $entityUrlItem = $this->getEntityUrlItem($website, $row);

            if ($entityUrlItem) {
                yield $entityUrlItem;
            }
        }

        $this->dispatchIterationEvent(UrlItemsProviderEvent::ON_END, $website, $version);
    }
```

For example, for limitations which are included in the web catalog (there are products from Product Content Variants, products included in categories and subcategories from Category Content Variants, products from Product Collection Variants) there are two listeners for these events:
```yaml
    oro_seo.event_listener.url_items_provider_start:
        class: Oro\Bundle\SEOBundle\EventListener\ProductUrlItemsProviderStartListener
        arguments:
            - '@oro_seo.limiter.web_catalog_product_limiter'
        tags:
            - { name: kernel.event_listener, event: oro_seo.event.url_items_provider_start.product, method: onStart }

    oro_seo.event_listener.url_items_provider_end:
        class: Oro\Bundle\SEOBundle\EventListener\ProductUrlItemsProviderEndListener
        arguments:
            - '@oro_seo.limiter.web_catalog_product_limiter'
        tags:
            - { name: kernel.event_listener, event: oro_seo.event.url_items_provider_end.product, method: onEnd }
```

For Limitation, `WebCatalogProductLimiter` is used. This class collects all appropriate products to `WebCatalogProductLimitation`.
Listener `RestrictSitemapProductByWebCatalogListener` restricts Sitemap products, taking into account only those that are in the `WebCatalogProductLimitation` table .
You can override `WebCatalogProductLimiter` or create your own `oro_seo.event.url_items_provider_start.*`, `oro_seo.event.url_items_provider_end.*` listeners to add products to Sitemap from your own sources.


### HOW to add new provider

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
### Hot to add new provider which will be available while the website is locked 

```yaml
    acme.sitemap.provider.router_sitemap_urls_provider:
        class: Acme\Bundle\SEOBundle\Sitemap\Provider\AcmeUrlsProvider
        public: false
        arguments:
            - '@router'
        tags:
            - { name: oro_seo.sitemap.website_access_denied_urls_provider, alias: 'acme_urls' }
```
If the URL provider should always be available, use both `oro_seo.sitemap.url_items_provider` and `oro_seo.sitemap.website_access_denied_urls_provider` tags.

The `oro_seo.event.restrict_sitemap_entity.my_provider` event is triggered during the query builder iteration.
Also, `oro_seo.event.url_items_provider_start.my_provider` and `oro_seo.event.url_items_provider_end.my_provider` events make some changes at the beginning and at the end of the `UrlItemProvider::getUrlItems` processing. These events may be used to restrict or modify the original query from third-party developers.

### HOW to customize sitemap provider logic

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

### HOW to make provider availability depend on the web catalog

A new `frontend_master_catalog` feature  was created to detect if web catalog restrictions should be applied. This feature may be useful for restricting entities based on the web catalog assignment.

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

and should be tagged with the `oro_featuretogle.feature` tag for the `frontend_master_catalog` feature.

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
The `oro:cron:sitemap:generate` command generates sitemap files for all providers into the `public/sitemaps/actual/` folder and logs the sitemap location in the *robots.txt* file.
Sitemap generation is a deferred process that is executed using the `SitemapGenerationProcessor.php` queue processor. For time-based sitemap generation on the predefined schedule, Oro application uses cron jobs.

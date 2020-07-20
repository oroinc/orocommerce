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
* In enterprise edition, there is an ability to disable sitemap generation for specific website with `Enable Sitemap Generation For Website` option of sitemap configuration for the website.
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

You can create your own `oro_seo.event.url_items_provider_start.*`, `oro_seo.event.url_items_provider_end.*` listeners to add products to Sitemap from your own sources.


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

*SitemapDumper* creates sitemap files for each configured website in the temp directory and logs sitemap location into appropriate files depending on the storage type:

* XmlSitemapIndexStorage - for index sitemap file
* XmlSitemapUrlsStorage - for particular sitemap file

All sitemap files are compressed with gzip utility and have *.gz* file extension.

At the end of the dumping process, the generated files are moved to the Gaufrette storage. By default, the storage is 
configured to use a file adapter, and the files will be stored in the `public/media/sitemaps/{websiteId}/` directory of 
your Oro application, where `{websiteId}` is the website Id.

**Robots.txt file**

Robot txt files are created for every configured website's domain name of the system. 
If some websites have the same domain name and different subfolders, the final robot txt file for such domain will have
a links to sitemaps from all such websites. 


The `config/{domain}.txt.dist`, where {domain} is the domain name, file is used as the base for every robots txt file.
If the file is absent, the `config/robots.txt.dist` file is used.
If the `robots.txt.dist` file is absent, the following data is used as the base:

```text
# www.robotstxt.org/
# www.google.com/support/webmasters/bin/answer.py?hl=en&answer=156449

User-agent: *

```

The *DumpRobotsTxtListener.php* adds the links to the sitemap index files.

At the finish of dumping process, generated robot txt files are moves to the Gaufrette storage. By default,
the storage is configured to use file adapter and files will be stored at `public/media/sitemaps/` directory
of your Oro application. 

The file names of dumped files have the next format: `robots.{domain}.txt`, where:

 - {domain} - the domain name of configured website url for website

To configure the redirect from the `/robots.txt` web request to the appropriate generated robot txt file, use
the following configuration of the web server:

- for Apache server, add the rewrite rule to the mod_rewire configuration block of the .htaccess file:

```text
RewriteRule ^robots.txt /media/sitemaps/3_your_domain.com.txt [L]
```
- for nginx web server, add the following configuration:

```text
location /robots.txt {
    alias /media/sitemaps/3_your_domain.com.txt;
} 
```

**Sitemap generation**

The cron command `oro:cron:sitemap:generate` adds a message to the queue to generate sitemap
and robot txt files.

Sitemap generation is a deferred process that is executed using the `SitemapGenerationProcessor.php` queue processor. For time-based sitemap generation on the predefined schedule, Oro application uses cron jobs.

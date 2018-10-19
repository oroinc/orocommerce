# OroRedirectBundle

OroRedirectBundle enables slug management for the product, category, brand, and landing pages in the OroCommerce storefront.

The bundle enables OroCommerce management console administrators to configure automatic slug generation and related options in the system configuration UI. It also provides the ability for content managers to modify slugs manually for every sluggable page.

# Semantic URLs
## Entity interfaces
Semantic URLs also sometimes referred to as clean URLs, RESTful URLs, user-friendly URLs, or search engine-friendly URLs, 
are Uniform Resource Locators (URLs) intended to improve the usability and accessibility of a website or 
web service by being immediately and intuitively meaningful to non-expert users.

Concepts
 - Slug Prototype - is a part of semantic URL that represents current entity without context part and unique suffixes
 - Slug - is a full representation of semantic URL with parent slug included and optional with automatically generated uniqueness suffix
 - _with redirect_ - means that on slug prototype change redirect record from old to new URL may be created (depends on system config and user choice).
 
Entity in system may be extended with just only Slug Prototype if it has no Slugs but only provides prototypes. Also entity 
may contain collection of related Slugs.

Entities that support Slug Prototypes should implement one of the following interfaces:
 - `Oro\Bundle\RedirectBundle\Entity\LocalizedSlugPrototypeAwareInterface` - for localized slug prototypes. Interface is implemented in `Oro\Bundle\RedirectBundle\Entity\LocalizedSlugPrototypeAwareTrait`
 - `Oro\Bundle\RedirectBundle\Entity\LocalizedSlugPrototypeWithRedirectAwareInterface` - for localized slug prototypes with redirect. Interface is implemented in `Oro\Bundle\RedirectBundle\Entity\LocalizedSlugPrototypeWithRedirectAwareTrait`
 - `Oro\Bundle\RedirectBundle\Entity\TextSlugPrototypeAwareInterface` - for localized slug prototypes where slugs are stored in text field. Interface is implemented in `Oro\Bundle\RedirectBundle\Entity\TextSlugPrototypeAwareTrait`
 - `Oro\Bundle\RedirectBundle\Entity\TextSlugPrototypeWithRedirectAwareInterface` - for localized slug prototypes where slugs are stored in text field with redirect. Interface is implemented in `Oro\Bundle\RedirectBundle\Entity\TextSlugPrototypeWithRedirectAwareTrait`
 
Entities that support Slugs should implement `Oro\Bundle\RedirectBundle\Entity\SlugAwareInterface` which is implemented in `Oro\Bundle\RedirectBundle\Entity\SlugAwareTrait`
In most cases `Oro\Bundle\RedirectBundle\Entity\SluggableInterface` may be used, which extends `LocalizedSlugPrototypeWithRedirectAwareInterface` and `SlugAwareInterface`.
Corresponding trait name is `SlugAwareTrait`

## Migration extension

To simplify sluggable entities management in migration `SlugExtension` was added. While creating new installer or update script which manage
sluggable entities just implement `SlugExtensionAwareInterface` and use methods of `SlugExtension` to add new slug prototype and slug relations:
 - `addLocalizedSlugPrototypes` - adds relation to localized slug prototype
 - `addSlugs` - creates slugs relation table

## Canonical URLs
 The canonical link relation specifies the preferred IRI from
   resources with duplicative content.  Common implementations of the
   canonical link relation are to specify the preferred version of an
   IRI from duplicate pages created with the addition of IRI parameters
   (e.g., session IDs) or to specify the single-page version as
   preferred over the same content separated on multiple component
   pages.
   
In Oro One of System URL or Semantic URL may be used as Canonical URL. This option is managed by `oro_redirect.canonical_url_type` system config option 

## Semantic URL caching

Each time sluggable URL is generated system should check database for existence of Semantic URL for generated with route parameters.
This may tend to huge amount of DB queries which may increase page response time and number of queries to DB. To avoid this situation
Semantic URLs may be cached. Oro provides 3 caches type and 2 URL providers.

### URL caches
Each Oro installation may be done in different environments, to give system administrators maximum flexibility OroCommerce provides three types of caches
which may be configured with DI parameter `oro_redirect.url_cache_type`. 
 - **storage** (default) - store cached URL in gropes. Best fit for filesystem based caches as it's usage minifies required space and number of available inodes.
   This type of caches group same urls. Grouping factor may be tuned with DI parameter `oro_redirect.url_storage_cache.split_deep` which is an integer in rage 1..32.
   Default is set to 2 which handles up to 1M of slugs. For installation that has more slugs it's recommended to increase this parameter. Note that increasing this option
   will lead to increasing number of cache files which may require more space and number of inodes.
 - **key_value** - store each cached value by it's key. Best fit for key-value based caches like redis
 - **local** - store caches in local array cache. May be used with `database` url provider which will allow Semantic URLs usage without their real caching in persistent cache

`oro_redirect.url_cache` service must be used for interaction with Semantic URL caches

### URL provider
Semantic URLs should be received from URL providers. This services interact with caches and provide urls which may be returned to output.
There are two providers in OroCommerce:
 - **cache** - reads data from `oro_redirect.url_cache`. Semantic URLs are available only after them appear in cache (URL is processed by MQ)
 - **database** - if URL was not found in decorated cache than this provider performs request to database and 
 in case when URL was found it is stored in cache. Using this provider you'll get Semantic URL immediately, but it may 
 require requests to database which may decrease performance

URL provider may be changed with DI parameter `oro_redirect.url_provider_type`

## Sluggable URLs matching
In Oro Symfony default routing was extended to match Semantic URLs. First system URLs are matched, than Semantic.
To skip some URL from slug matching it should be added to skip list by calling `addSkippedUrlPattern` of `oro_redirect.routing.matched_url_decision_maker` service

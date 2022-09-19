<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Psr\Container\ContainerInterface;

/**
 * Please use this trait if you need reindex actions in your tests.
 *
 * @method static ContainerInterface getContainer()
 */
trait WebsiteSearchExtensionTrait
{
    use SearchExtensionTrait {
        ensureItemsLoaded as baseEnsureItemsLoaded;
    }
    use DefaultWebsiteIdTestTrait;

    protected static function reindexProductData(): void
    {
        self::getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    protected static function ensureItemsLoaded(
        string $classOrAlias,
        int $itemsCount,
        string $searchService = 'oro_website_search.engine'
    ): void {
        if (class_exists($classOrAlias)) {
            $websiteId = self::getDefaultWebsiteId();
            $alias = self::getIndexAlias($classOrAlias, [WebsiteIdPlaceholder::NAME => $websiteId]);
        } else {
            $alias = $classOrAlias;
        }

        self::baseEnsureItemsLoaded($alias, $itemsCount, $searchService);
    }

    protected static function getIndexAlias(string $className, array $placeholders): string
    {
        $indexAliasTemplate = self::getContainer()
            ->get('oro_website_search.provider.search_mapping')
            ->getEntityAlias($className);

        return self::getContainer()
            ->get('oro_website_search.placeholder_decorator')
            ->replace($indexAliasTemplate, $placeholders);
    }

    /**
     * @param string[]|string|null $class
     * @param array $context
     */
    protected static function resetIndex(array|string|null $class = null, array $context = []): void
    {
        self::getContainer()->get('oro_website_search.indexer')->resetIndex($class, $context);
    }
}

<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

/** Please use this trait if you need reindex actions in your tests */
trait WebsiteSearchExtensionTrait
{
    use SearchExtensionTrait;

    protected function reindexProductData()
    {
        $this->getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
        $this->getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    private function getIndexAlias(string $className, array $placeholders): string
    {
        $indexAliasTemplate = $this->getContainer()
            ->get('oro_website_search.provider.search_mapping')
            ->getEntityAlias($className);

        return $this->getContainer()->get('oro_website_search.placeholder_decorator')
            ->replace($indexAliasTemplate, $placeholders);
    }
}

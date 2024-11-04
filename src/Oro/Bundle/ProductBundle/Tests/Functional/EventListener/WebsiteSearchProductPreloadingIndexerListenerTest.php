<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductPreloadingIndexerListener;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchProductPreloadingIndexerListenerTest extends WebTestCase
{
    private ?WebsiteSearchProductPreloadingIndexerListener $listener = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->listener = self::getContainer()->get(
            'oro_product.event_listener.website_search_product_preloading_index'
        );

        $this->listener->setEnabled(true);

        $this->loadFixtures([LoadProductKitData::class]);
    }

    public function testOnWebsiteSearchIndex(): void
    {
        $context = [AbstractIndexer::CONTEXT_CURRENT_WEBSITE_ID_KEY => $this->getDefaultWebsite()->getId()];
        $entities = [
            $this->getReference(LoadProductKitData::PRODUCT_KIT_1),
            $this->getReference(LoadProductKitData::PRODUCT_KIT_2),
        ];

        foreach ($entities as $entity) {
            self::assertFalse($entity->getKitItems()->isInitialized());
        }

        $event = new IndexEntityEvent(Product::class, $entities, $context);
        $this->listener->onWebsiteSearchIndex($event);

        foreach ($entities as $entity) {
            self::assertTrue($entity->getKitItems()->isInitialized());

            foreach ($entity->getKitItems() as $kitItem) {
                self::assertTrue($kitItem->getLabels()->isInitialized());
                self::assertTrue($kitItem->getKitItemProducts()->isInitialized());
            }
        }
    }

    private function getDefaultWebsite(): Website
    {
        return self::getContainer()
            ->get('doctrine')
            ->getRepository(Website::class)
            ->getDefaultWebsite();
    }
}

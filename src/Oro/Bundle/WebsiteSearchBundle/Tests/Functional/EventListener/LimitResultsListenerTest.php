<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\EventListener;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadSearchProductData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LimitResultsListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadSearchProductData::class]);

        self::getContainer()->get('oro_website_search.indexer')->reindex();
    }

    public function testSearchWithLimit(): void
    {
        $query = new Query();
        $query->addSelect('text.sku')->from('oro_product_WEBSITE_ID');
        $query->getCriteria()->setMaxResults(1001);

        $searchEngine = self::getContainer()->get('oro_website_search.engine');

        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get('request_stack');
        // Emulate frontend request
        $requestStack->push(new Request());

        $result = $searchEngine->search($query);

        self::assertEquals(1000, $result->count());
    }
}

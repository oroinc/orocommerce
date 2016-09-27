<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Driver;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;

/**
 * @dbIsolationPerTest
 */
class OrmAccountPartialUpdateDriverTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadItemData::class]);

        /*
        if ($this->getContainer()->getParameter('') != 'orm') {
            $this->markTestSkipped('This test depends on orm search engine');
        }
        */
    }

    public function testDeleteAccountVisibility()
    {
        /** @var OrmIndexer $indexer */
        $indexer = $this->getContainer()->get('oro_website_search.indexer');

        $indexer->reindex(Product::class);

        $doctrine = $this->getContainer()->get('doctrine');
        $repository = $doctrine
            ->getManagerForClass(IndexInteger::class)
            ->getRepository(IndexInteger::class);

        $visibilities = $repository->findAll();
    }
}

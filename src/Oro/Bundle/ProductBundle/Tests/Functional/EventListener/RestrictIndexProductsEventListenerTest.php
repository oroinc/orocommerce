<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;

/**
 * @dbIsolationPerTest
 */
class RestrictIndexProductsEventListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        /** @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject */
        $frontendHelperMock = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $frontendHelperMock->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->getContainer()->set('orob2b_frontend.request.frontend_helper', $frontendHelperMock);
        $this->loadFixtures([LoadProductData::class]);
    }

    public function testRestrictIndexProductsEventListener()
    {
        $website = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroWebsiteBundle:Website')
            ->getDefaultWebsite();

        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $searchEngine = $this->getContainer()->get('oro_website_search.engine');
        $indexer->reindex(Product::class, [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $website->getId()]);

        $query = new Query();
        $query->from('oro_product_product_WEBSITE_ID');
        $query->select('recordTitle');
        $result = $searchEngine->search($query);
        $this->assertEquals(6, $result->getRecordsCount());
        $values = $result->getElements();
        $this->assertEquals($this->getReference('product.1'), $values[0]->getEntity());
        $this->assertEquals($this->getReference('product.2'), $values[1]->getEntity());
        $this->assertEquals($this->getReference('product.3'), $values[2]->getEntity());
        $this->assertEquals($this->getReference('product.6'), $values[3]->getEntity());
        $this->assertEquals($this->getReference('product.7'), $values[4]->getEntity());
        $this->assertEquals($this->getReference('product.8'), $values[5]->getEntity());
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

/**
 * @dbIsolation
 */
class ContentNodeRepositoryTest extends WebTestCase
{
    /**
     * @var ContentNodeRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadContentNodesData::class
            ]
        );
        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);
    }

    public function testGetRootNodeByWebCatalog()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $expectedRoot = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $this->assertEquals($expectedRoot, $this->repository->getRootNodeByWebCatalog($webCatalog));
    }

    public function testGetRootNodeByWebCatalogWithoutRoot()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_3);
        $actual = $this->repository->getRootNodeByWebCatalog($webCatalog);
        $this->assertNull($actual);
    }
}

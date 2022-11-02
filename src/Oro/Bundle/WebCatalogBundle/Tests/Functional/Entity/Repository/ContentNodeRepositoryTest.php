<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantsData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class ContentNodeRepositoryTest extends WebTestCase
{
    /**
     * @var ContentNodeRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadContentVariantsData::class]);

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

    public function testGetContentVariantQueryBuilder()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_2);
        /** @var ContentNode $node */
        $node = $this->getReference(LoadContentNodesData::CATALOG_2_ROOT);
        /** @var ContentVariant $variant */
        $variant = $this->getReference(LoadContentVariantsData::ROOT_VARIANT);

        $queryBuilder = $this->repository->getContentVariantQueryBuilder($webCatalog);
        $this->assertEquals(
            [[
                'nodeId' => $node->getId(),
                'variantId' => $variant->getId(),
            ]],
            $queryBuilder->getQuery()->getArrayResult()
        );
    }

    public function testGetNodesByIds()
    {
        /** @var ContentNode $firstNode */
        $firstNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        /** @var ContentNode $secondNode */
        $secondNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        /** @var ContentNode $thirdNode */
        $thirdNode = $this->getReference(LoadContentNodesData::CATALOG_2_ROOT);

        $nodeIds = [$firstNode->getId(), $secondNode->getId(), $thirdNode->getId()];

        $nodes = $this->repository->getNodesByIds($nodeIds);

        $this->assertSameSize($nodeIds, $nodes);
        $this->assertContains($firstNode, $nodes);
        $this->assertContains($secondNode, $nodes);
        $this->assertContains($thirdNode, $nodes);
    }

    public function testGetDirectNodesWithParentScopeUsed()
    {
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);

        $actual = $this->repository->getDirectNodesWithParentScopeUsed($contentNode);

        $this->assertEquals([$this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1)], $actual);
    }

    public function testGetSlugPrototypesByParent()
    {
        $parentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $actual = $this->repository->getSlugPrototypesByParent($parentNode);
        sort($actual);

        $this->assertEquals(['web_catalog.node.1.1.1', 'web_catalog.node.1.1.2'], $actual);
    }

    public function testGetSlugPrototypesByParentRootLevel()
    {
        $actual = $this->repository->getSlugPrototypesByParent();
        sort($actual);

        $this->assertEquals(['web_catalog.node.1.root', 'web_catalog.node.2.root'], $actual);
    }

    public function testGetSlugPrototypesByParentWithoutNode()
    {
        $parentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $skipNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1);
        $actual = $this->repository->getSlugPrototypesByParent($parentNode, $skipNode);

        $this->assertEquals(['web_catalog.node.1.1.2'], $actual);
    }
}

<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentVariantsData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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

        $this->repository = self::getContainer()->get('doctrine')
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class);
    }

    public function testGetRootNodeByWebCatalog(): void
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $expectedRoot = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        self::assertEquals($expectedRoot, $this->repository->getRootNodeByWebCatalog($webCatalog));
    }

    public function testGetRootNodeByWebCatalogWithoutRoot(): void
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_3);
        $actual = $this->repository->getRootNodeByWebCatalog($webCatalog);
        self::assertNull($actual);
    }

    public function testGetRootNodeIdByWebCatalog(): void
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_1);
        $expectedRoot = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        self::assertEquals($expectedRoot->getId(), $this->repository->getRootNodeIdByWebCatalog($webCatalog));
    }

    public function testGetRootNodeIdByWebCatalogWithoutRoot(): void
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_3);
        $actual = $this->repository->getRootNodeIdByWebCatalog($webCatalog);
        self::assertNull($actual);
    }

    public function testGetContentVariantQueryBuilder(): void
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_2);
        /** @var ContentNode $node */
        $node = $this->getReference(LoadContentNodesData::CATALOG_2_ROOT);
        /** @var ContentVariant $variant */
        $variant = $this->getReference(LoadContentVariantsData::ROOT_VARIANT);

        $queryBuilder = $this->repository->getContentVariantQueryBuilder($webCatalog);
        self::assertEquals(
            [
                [
                    'nodeId' => $node->getId(),
                    'variantId' => $variant->getId(),
                ],
            ],
            $queryBuilder->getQuery()->getArrayResult()
        );
    }

    public function testGetNodesByIds(): void
    {
        /** @var ContentNode $firstNode */
        $firstNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        /** @var ContentNode $secondNode */
        $secondNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        /** @var ContentNode $thirdNode */
        $thirdNode = $this->getReference(LoadContentNodesData::CATALOG_2_ROOT);

        $nodeIds = [$firstNode->getId(), $secondNode->getId(), $thirdNode->getId()];

        $nodes = $this->repository->getNodesByIds($nodeIds);

        self::assertSameSize($nodeIds, $nodes);
        self::assertContains($firstNode, $nodes);
        self::assertContains($secondNode, $nodes);
        self::assertContains($thirdNode, $nodes);
    }

    public function testGetDirectNodesWithParentScopeUsed(): void
    {
        $contentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);

        $actual = $this->repository->getDirectNodesWithParentScopeUsed($contentNode);

        self::assertEquals([$this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1)], $actual);
    }

    public function testGetSlugPrototypesByParent(): void
    {
        $parentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $actual = $this->repository->getSlugPrototypesByParent($parentNode);
        sort($actual);

        self::assertEquals(['web_catalog.node.1.1.1', 'web_catalog.node.1.1.2'], $actual);
    }

    public function testGetSlugPrototypesByParentRootLevel(): void
    {
        $actual = $this->repository->getSlugPrototypesByParent();
        sort($actual);

        self::assertEquals(['web_catalog.node.1.root', 'web_catalog.node.2.root'], $actual);
    }

    public function testGetSlugPrototypesByParentWithoutNode(): void
    {
        $parentNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $skipNode = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1);
        $actual = $this->repository->getSlugPrototypesByParent($parentNode, $skipNode);

        self::assertEquals(['web_catalog.node.1.1.2'], $actual);
    }

    public function testGetContentNodePlainTreeQueryBuilder(): void
    {
        $contentNode1 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $contentNode2 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $contentNode3 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1);
        $contentNode4 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2);
        $contentNode5 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2);

        $qb = $this->repository->getContentNodePlainTreeQueryBuilder($contentNode1);
        self::assertEquals(
            [
                $contentNode1->getId(),
                $contentNode2->getId(),
                $contentNode3->getId(),
                $contentNode4->getId(),
                $contentNode5->getId(),
            ],
            $qb->select('node.id')->getQuery()->execute([], AbstractQuery::HYDRATE_SCALAR_COLUMN)
        );
    }

    public function testGetContentNodePlainTreeQueryBuilderWithDepth(): void
    {
        $contentNode1 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $contentNode2 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);
        $contentNode3 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2);

        $qb = $this->repository->getContentNodePlainTreeQueryBuilder($contentNode1, 1);
        self::assertEquals(
            [$contentNode1->getId(), $contentNode2->getId(), $contentNode3->getId()],
            $qb->select('node.id')->getQuery()->execute([], AbstractQuery::HYDRATE_SCALAR_COLUMN)
        );
    }

    public function testGetContentNodesDataWhenEmpty(): void
    {
        self::assertEquals(
            [],
            $this->repository->getContentNodesData([PHP_INT_MAX])
        );
    }

    public function testGetContentNodesData(): void
    {
        $contentNode1 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT);
        $contentNode2 = $this->getReference(LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1);

        $result = $this->repository->getContentNodesData([$contentNode1->getId(), $contentNode2->getId()]);

        self::assertContentNodeData($contentNode1, $result[0]);
        self::assertContentNodeData($contentNode2, $result[1]);
    }

    private static function assertContentNodeData(ContentNode $contentNode, array $data): void
    {
        self::assertSame($contentNode->getParentNode()?->getId(), $data['parentNode']['id'] ?? null);
        self::assertSame($contentNode->getId(), $data['id']);
        self::assertEquals($contentNode->getTitles()[0]?->getString(), $data['titles'][0]['string']);
        self::assertEquals($contentNode->getLocalizedUrls()[0]?->getText(), $data['localizedUrls'][0]['text']);
    }
}

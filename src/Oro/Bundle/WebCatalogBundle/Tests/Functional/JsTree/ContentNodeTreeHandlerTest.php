<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\JsTree;

use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\JsTree\ContentNodeTreeHandler;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadContentNodesData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Oro\Component\Tree\Test\AbstractTreeHandlerTestCase;

/**
 * @property ContentNodeTreeHandler $handler
 */
class ContentNodeTreeHandlerTest extends AbstractTreeHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFixtures(): array
    {
        return [LoadContentNodesData::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlerId(): string
    {
        return 'oro_web_catalog.content_node_tree_handler';
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreateTree(?string $entityReference, bool $includeRoot, array $expectedData)
    {
        $entity = null;
        if (null !== $entityReference) {
            /** @var ContentNode $entity */
            $entity = $this->getReference($entityReference);
        }

        $expectedData = array_reduce($expectedData, function ($result, $data) {
            /** @var ContentNode $entity */
            $entity = $this->getReference($data['entity']);
            $data['id'] = $entity->getId();
            $data['text'] = $this->getTitle($entity);
            if ($data['parent'] !== AbstractTreeHandler::ROOT_PARENT_VALUE) {
                $data['parent'] = $this->getReference($data['parent'])->getId();
            }
            unset($data['entity']);
            $result[$data['id']] = $data;
            return $result;
        }, []);

        $this->assertTreeCreated($expectedData, $entity, $includeRoot);
    }

    public function testCreateTreeForEmptyRoot()
    {
        $this->assertEquals([], $this->handler->createTree());
    }

    public function createDataProvider(): array
    {
        return [
            'CATALOG_1_ROOT without root' => [
                'root' => LoadContentNodesData::CATALOG_1_ROOT,
                'includeRoot' => false,
                'expectedData' => [
                    [
                        'entity' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2,
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2,
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
            'CATALOG_1_ROOT with root' => [
                'root' => LoadContentNodesData::CATALOG_1_ROOT,
                'includeRoot' => true,
                'expectedData' => [
                    [
                        'entity' => LoadContentNodesData::CATALOG_1_ROOT,
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2,
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2,
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @dataProvider moveDataProvider
     */
    public function testMove(
        string $entityReference,
        string $parent,
        int $position,
        array $expectedStatus,
        array $expectedData
    ) {
        $entityId = $this->getReference($entityReference)->getId();
        if ($parent !== AbstractTreeHandler::ROOT_PARENT_VALUE) {
            $parent = $this->getReference($parent)->getId();
        }

        $this->assertNodeMove($expectedStatus, $expectedData, $entityId, $parent, $position);
    }

    public function moveDataProvider(): array
    {
        return [
            [
                'entity' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1,
                'parent' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2,
                'position' => 0,
                'expectedStatus' => ['status' => AbstractTreeHandler::SUCCESS_STATUS],
                'expectedData' => [
                    LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_1 => [
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2
                    ],
                    LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1_2 => [
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1
                    ],
                    LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_1 => [
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT
                    ],
                    LoadContentNodesData::CATALOG_1_ROOT_SUBNODE_2 => [
                        'parent' => LoadContentNodesData::CATALOG_1_ROOT
                    ],
                    LoadContentNodesData::CATALOG_1_ROOT => [
                    ],
                ]
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getActualNodeHierarchy(int $entityId, int $parentId, int $position): array
    {
        $entities = $this->getContainer()->get('doctrine')
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class)
            ->findBy(
                ['webCatalog' => $this->getReference(LoadWebCatalogData::CATALOG_1)],
                ['level' => 'DESC', 'left' => 'DESC']
            );
        return array_reduce($entities, function ($result, ContentNode $node) {
            $result[$this->getTitle($node)] = [];
            if ($node->getParentNode()) {
                $result[$this->getTitle($node)]['parent'] = $this->getTitle($node->getParentNode());
            }
            return $result;
        }, []);
    }

    public function testGetTreeRootByWebCatalog()
    {
        /** @var WebCatalog $webCatalog */
        $webCatalog = $this->getReference(LoadWebCatalogData::CATALOG_2);
        /** @var ContentNode $expectedRoot */
        $expectedRoot = $this->getReference(LoadContentNodesData::CATALOG_2_ROOT);

        $this->assertEquals($expectedRoot, $this->handler->getTreeRootByWebCatalog($webCatalog));
    }

    protected function getTitle(ContentNode $contentNode): string
    {
        $titleValue = $this->getContainer()
            ->get('oro_locale.helper.localization')
            ->getFirstNonEmptyLocalizedValue($contentNode->getTitles());

        return $titleValue ? $titleValue->getString() : '';
    }
}

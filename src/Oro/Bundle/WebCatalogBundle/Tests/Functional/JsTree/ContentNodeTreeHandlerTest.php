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
 * @dbIsolation
 * @property ContentNodeTreeHandler $handler
 */
class ContentNodeTreeHandlerTest extends AbstractTreeHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFixtures()
    {
        return [
            LoadContentNodesData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlerId()
    {
        return 'oro_web_catalog.content_node_tree_handler';
    }

    /**
     * @dataProvider createDataProvider
     * @param string|null $entityReference
     * @param bool $includeRoot
     * @param array $expectedData
     */
    public function testCreateTree($entityReference, $includeRoot, array $expectedData)
    {
        $entity = null;
        if ($entityReference !== null) {
            /** @var ContentNode $entity */
            $entity = $this->getReference($entityReference);
        }

        $expectedData = array_reduce($expectedData, function ($result, $data) {
            /** @var ContentNode $entity */
            $entity = $this->getReference($data['entity']);
            $data['id'] = $entity->getId();
            $data['text'] = $entity->getName();
            if ($data['parent'] !== AbstractTreeHandler::ROOT_PARENT_VALUE) {
                $data['parent'] = $this->getReference($data['parent'])->getId();
            }
            unset($data['entity']);
            $result[$data['id']] = $data;
            return $result;
        }, []);

        $this->assertTreeCreated($expectedData, $entity, $includeRoot);
    }

    /**
     * @return array
     */
    public function createDataProvider()
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
     * @param string $entityReference
     * @param string $parent
     * @param int $position
     * @param array $expectedStatus
     * @param array $expectedData
     */
    public function testMove($entityReference, $parent, $position, array $expectedStatus, array $expectedData)
    {
        $entityId = $this->getReference($entityReference)->getId();
        if ($parent !== AbstractTreeHandler::ROOT_PARENT_VALUE) {
            $parent = $this->getReference($parent)->getId();
        }

        $this->assertNodeMove($expectedStatus, $expectedData, $entityId, $parent, $position);
    }

    /**
     * @return array
     */
    public function moveDataProvider()
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
    protected function getActualNodeHierarchy($entityId, $parentId, $position)
    {
        $entities = $this->getContainer()->get('doctrine')
            ->getManagerForClass(ContentNode::class)
            ->getRepository(ContentNode::class)
            ->findBy(
                ['webCatalog' => $this->getReference(LoadWebCatalogData::CATALOG_1)],
                ['level' => 'DESC', 'left' => 'DESC']
            );
        return array_reduce($entities, function ($result, ContentNode $node) {
            $result[$node->getName()] = [];
            if ($node->getParentNode()) {
                $result[$node->getName()]['parent'] = $node->getParentNode()->getName();
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
}

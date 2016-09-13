<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\JsTree;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Oro\Component\Tree\Test\AbstractTreeHandlerTestCase;

/**
 * @dbIsolation
 */
class CategoryTreeHandlerTest extends AbstractTreeHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFixtures()
    {
        return 'Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData';
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlerId()
    {
        return 'oro_catalog.category_tree_handler';
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
            /** @var Category $entity */
            $entity = $this->getReference($entityReference);
        }

        $expectedData = array_reduce($expectedData, function ($result, $data) {
            /** @var Category $entity */
            $entity = $this->getReference($data['entity']);
            $data['id'] = $entity->getId();
            $data['text'] = $entity->getDefaultTitle()->getString();
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
            [
                'root' => LoadCategoryData::SECOND_LEVEL1,
                'includeRoot' => true,
                'expectedData' => [
                    [
                        'entity' => LoadCategoryData::SECOND_LEVEL1,
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadCategoryData::THIRD_LEVEL1,
                        'parent' => LoadCategoryData::SECOND_LEVEL1,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadCategoryData::FOURTH_LEVEL1,
                        'parent' => LoadCategoryData::THIRD_LEVEL1,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
            [
                'root' => LoadCategoryData::SECOND_LEVEL1,
                'includeRoot' => false,
                'expectedData' => [
                    [
                        'entity' => LoadCategoryData::THIRD_LEVEL1,
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => LoadCategoryData::FOURTH_LEVEL1,
                        'parent' => LoadCategoryData::THIRD_LEVEL1,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
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
                'entity' => LoadCategoryData::FOURTH_LEVEL1,
                'parent' => LoadCategoryData::THIRD_LEVEL2,
                'position' => 1,
                'expectedStatus' => ['status' => AbstractTreeHandler::SUCCESS_STATUS],
                'expectedData' => [
                    'Master catalog' => [],
                    LoadCategoryData::FIRST_LEVEL => [
                        'parent' => 'Master catalog'
                    ],
                    LoadCategoryData::SECOND_LEVEL1 => [
                        'parent' => LoadCategoryData::FIRST_LEVEL
                    ],
                    LoadCategoryData::THIRD_LEVEL1 => [
                        'parent' => LoadCategoryData::SECOND_LEVEL1
                    ],
                    LoadCategoryData::FOURTH_LEVEL1 => [
                        'parent' => LoadCategoryData::THIRD_LEVEL2
                    ],
                    LoadCategoryData::SECOND_LEVEL2 => [
                        'parent' => LoadCategoryData::FIRST_LEVEL
                    ],
                    LoadCategoryData::THIRD_LEVEL2 => [
                        'parent' => LoadCategoryData::SECOND_LEVEL2
                    ],
                    LoadCategoryData::FOURTH_LEVEL2 => [
                        'parent' => LoadCategoryData::THIRD_LEVEL2
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
        $entities = $this->getContainer()->get('doctrine')->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')->findBy([], ['level' => 'DESC', 'left' => 'DESC']);
        return array_reduce($entities, function ($result, Category $category) {
            $result[$category->getDefaultTitle()->getString()] = [];
            if ($category->getParentCategory()) {
                $result[$category->getDefaultTitle()->getString()]['parent'] = $category->getParentCategory()
                    ->getDefaultTitle()->getString();
            }
            return $result;
        }, []);
    }
}

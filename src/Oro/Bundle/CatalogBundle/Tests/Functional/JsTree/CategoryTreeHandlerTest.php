<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\JsTree;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Oro\Component\Tree\Test\AbstractTreeHandlerTestCase;

class CategoryTreeHandlerTest extends AbstractTreeHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFixtures(): array
    {
        return [LoadCategoryData::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlerId(): string
    {
        return 'oro_catalog.category_tree_handler';
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreateTree(?string $entityReference, bool $includeRoot, array $expectedData)
    {
        $entity = null;
        if (null !== $entityReference) {
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

    public function createDataProvider(): array
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

    public function testCreateTreeByMasterCatalogRoot()
    {
        $rootId = 1;
        /** @var Category $firstLevel */
        $firstLevel = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        /** @var Category $secondLevel1 */
        $secondLevel1 = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        /** @var Category $thirdLevel1 */
        $thirdLevel1 = $this->getReference(LoadCategoryData::THIRD_LEVEL1);
        /** @var Category $fourthLevel1 */
        $fourthLevel1 = $this->getReference(LoadCategoryData::FOURTH_LEVEL1);
        /** @var Category $secondLevel2 */
        $secondLevel2 = $this->getReference(LoadCategoryData::SECOND_LEVEL2);
        /** @var Category $thirdLevel2 */
        $thirdLevel2 = $this->getReference(LoadCategoryData::THIRD_LEVEL2);
        /** @var Category $fourthLevel2 */
        $fourthLevel2 = $this->getReference(LoadCategoryData::FOURTH_LEVEL2);

        $expectedData = [
            [
                'id' => $rootId,
                'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                'state' => [
                    'opened' => true
                ],
                'text' => 'All Products'
            ],
            [
                'id' => $firstLevel->getId(),
                'parent' => $rootId,
                'state' => [
                    'opened' => false
                ],
                'text' => $firstLevel->getDefaultTitle()->getString()
            ],
            [
                'id' => $secondLevel1->getId(),
                'parent' => $firstLevel->getId(),
                'state' => [
                    'opened' => false
                ],
                'text' => $secondLevel1->getDefaultTitle()->getString()
            ],
            [
                'id' => $thirdLevel1->getId(),
                'parent' => $secondLevel1->getId(),
                'state' => [
                    'opened' => false
                ],
                'text' => $thirdLevel1->getDefaultTitle()->getString()
            ],
            [
                'id' => $fourthLevel1->getId(),
                'parent' => $thirdLevel1->getId(),
                'state' => [
                    'opened' => false
                ],
                'text' => $fourthLevel1->getDefaultTitle()->getString()
            ],
            [
                'id' => $secondLevel2->getId(),
                'parent' => $firstLevel->getId(),
                'state' => [
                    'opened' => false
                ],
                'text' => $secondLevel2->getDefaultTitle()->getString()
            ],
            [
                'id' => $thirdLevel2->getId(),
                'parent' => $secondLevel2->getId(),
                'state' => [
                    'opened' => false
                ],
                'text' => $thirdLevel2->getDefaultTitle()->getString()
            ],
            [
                'id' => $fourthLevel2->getId(),
                'parent' => $thirdLevel2->getId(),
                'state' => [
                    'opened' => false
                ],
                'text' => $fourthLevel2->getDefaultTitle()->getString()
            ]
        ];

        $this->setAdminToken();

        $actualTree = $this->handler->createTreeByMasterCatalogRoot();
        $actualTree = array_reduce($actualTree, function ($result, $data) {
            $result[] = $data;
            return $result;
        }, []);
        ksort($expectedData);
        ksort($actualTree);
        $this->assertEquals($expectedData, $actualTree);
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
                'entity' => LoadCategoryData::FOURTH_LEVEL1,
                'parent' => LoadCategoryData::THIRD_LEVEL2,
                'position' => 1,
                'expectedStatus' => ['status' => AbstractTreeHandler::SUCCESS_STATUS],
                'expectedData' => [
                    'All Products' => [],
                    LoadCategoryData::FIRST_LEVEL => [
                        'parent' => 'All Products'
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
    protected function getActualNodeHierarchy(int $entityId, int $parentId, int $position): array
    {
        $entities = $this->getContainer()->get('doctrine')
            ->getRepository(Category::class)
            ->findBy([], ['level' => 'DESC', 'left' => 'DESC']);
        return array_reduce($entities, function ($result, Category $category) {
            $result[$category->getDefaultTitle()->getString()] = [];
            if ($category->getParentCategory()) {
                $result[$category->getDefaultTitle()->getString()]['parent'] = $category->getParentCategory()
                    ->getDefaultTitle()->getString();
            }
            return $result;
        }, []);
    }

    private function setAdminToken()
    {
        $container = self::getContainer();
        /** @var Organization $organization */
        $organization = $container->get('doctrine')
            ->getRepository(Organization::class)
            ->getFirst();

        $adminToken = new UsernamePasswordOrganizationToken(
            'admin',
            'admin',
            'key',
            $organization
        );

        $container->get('security.token_storage')->setToken($adminToken);
    }
}

<?php

namespace Oro\Bundle\MenuBundle\Tests\Functional\JsTree;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\MenuBundle\Entity\MenuItem;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Oro\Component\Tree\Test\AbstractTreeHandlerTestCase;

/**
 * @dbIsolation
 */
class MenuItemTreeHandlerTest extends AbstractTreeHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFixtures()
    {
        return 'Oro\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData';
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlerId()
    {
        return 'oro_menu.tree.menu_item_tree_handler';
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
            /** @var MenuItem $entity */
            $entity = $this->getReference($entityReference);
        }

        $expectedData = array_reduce($expectedData, function ($result, $data) {
            /** @var MenuItem $entity */
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'menu_item.1 without root' => [
                'root' => 'menu_item.1',
                'includeRoot' => false,
                'expectedData' => [
                    [
                        'entity' => 'menu_item.1_2',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => 'menu_item.1_3',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
            'menu_item.1' => [
                'root' => 'menu_item.1',
                'includeRoot' => true,
                'expectedData' => [
                    [
                        'entity' => 'menu_item.1',
                        'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => 'menu_item.1_2',
                        'parent' => 'menu_item.1',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => 'menu_item.1_3',
                        'parent' => 'menu_item.1',
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function moveDataProvider()
    {
        return [
            [
                'entity' => 'menu_item.1_3',
                'parent' => 'menu_item.1_2',
                'position' => 0,
                'expectedStatus' => ['status' => AbstractTreeHandler::SUCCESS_STATUS],
                'expectedData' => [
                    'menu_item.1' => [],
                    'menu_item.1_2' => [
                        'parent' => 'menu_item.1'
                    ],
                    'menu_item.1_3' => [
                        'parent' => 'menu_item.1_2'
                    ],
                ]
            ],
            [
                'entity' => 'menu_item.4_5_6',
                'parent' => 'menu_item.4',
                'position' => 1,
                'expectedStatus' => ['status' => AbstractTreeHandler::SUCCESS_STATUS],
                'expectedData' => [
                    'menu_item.4' => [],
                    'menu_item.4_5' => [
                        'parent' => 'menu_item.4'
                    ],
                    'menu_item.4_5_6' => [
                        'parent' => 'menu_item.4'
                    ],
                    'menu_item.4_5_7' => [
                        'parent' => 'menu_item.4_5'
                    ],
                    'menu_item.4_5_6_8' => [
                        'parent' => 'menu_item.4_5_6'
                    ],
                ]
            ],
            [
                'entity' => 'menu_item.4_5_6',
                'parent' => AbstractTreeHandler::ROOT_PARENT_VALUE,
                'position' => 1,
                'expectedStatus' => [
                    'status' => AbstractTreeHandler::ERROR_STATUS,
                    'error' => 'Existing menu can\'t be the root',
                ],
                'expectedData' => [
                    'menu_item.4' => [],
                    'menu_item.4_5' => [
                        'parent' => 'menu_item.4'
                    ],
                    'menu_item.4_5_6' => [
                        'parent' => 'menu_item.4'
                    ],
                    'menu_item.4_5_7' => [
                        'parent' => 'menu_item.4_5'
                    ],
                    'menu_item.4_5_6_8' => [
                        'parent' => 'menu_item.4_5_6'
                    ],
                ]
            ],
            [
                'entity' => 'menu_item.4_5_6',
                'parent' => 'menu_item.1',
                'position' => 0,
                'expectedStatus' => [
                    'status' => AbstractTreeHandler::ERROR_STATUS,
                    'error' => 'You can\'t move Menu Item to another menu.',
                ],
                'expectedData' => [
                    'menu_item.4' => [],
                    'menu_item.4_5' => [
                        'parent' => 'menu_item.4'
                    ],
                    'menu_item.4_5_6' => [
                        'parent' => 'menu_item.4'
                    ],
                    'menu_item.4_5_7' => [
                        'parent' => 'menu_item.4_5'
                    ],
                    'menu_item.4_5_6_8' => [
                        'parent' => 'menu_item.4_5_6'
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
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroMenuBundle:MenuItem');
        $entity = $em->getReference('OroMenuBundle:MenuItem', $entityId);
        $entities = $em->getRepository('OroMenuBundle:MenuItem')
            ->findBy(['root' => $entity->getRoot()], ['level' => 'DESC', 'left' => 'DESC']);
        return array_reduce($entities, function ($result, MenuItem $menuItem) {
            $result[$menuItem->getDefaultTitle()->getString()] = [];
            if ($menuItem->getParent()) {
                $result[$menuItem->getDefaultTitle()->getString()]['parent'] = $menuItem->getParent()
                    ->getDefaultTitle()->getString();
            }
            return $result;
        }, []);
    }
}

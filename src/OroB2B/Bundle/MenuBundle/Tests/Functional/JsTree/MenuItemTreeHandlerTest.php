<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\JsTree;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\JsTree\MenuItemTreeHandler;

/**
 * @dbIsolation
 */
class MenuItemTreeHandlerTest extends WebTestCase
{
    /**
     * @var MenuItemTreeHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData']);

        $this->handler = $this->getContainer()->get('orob2b_menu.tree.menu_item_tree_handler');
    }

    /**
     * @dataProvider createDataProvider
     * @param string|null $menuItem
     * @param array $expectedData
     */
    public function testCreateTree($menuItem, array $expectedData)
    {
        if ($menuItem !== null) {
            /** @var MenuItem $menuItem */
            $menuItem = $this->getReference($menuItem);
        }

        $this->assertTreeCreated($expectedData, $menuItem, true);
        $this->assertTreeCreated($expectedData, $menuItem, false);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'menu_item.1' => [
                'root' => 'menu_item.1',
                'expectedData' => [
                    [
                        'entity' => 'menu_item.1_2',
                        'parent' => MenuItemTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => 'menu_item.1_3',
                        'parent' => MenuItemTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
            'menu_item.4' => [
                'root' => 'menu_item.4',
                'expectedData' => [
                    [
                        'entity' => 'menu_item.4_5',
                        'parent' => MenuItemTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => 'menu_item.4_5_6',
                        'parent' => 'menu_item.4_5',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
            'all' => [
                'root' => null,
                'expectedData' => [
                    [
                        'entity' => 'menu_item.1',
                        'parent' => MenuItemTreeHandler::ROOT_PARENT_VALUE,
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
                    [
                        'entity' => 'menu_item.4',
                        'parent' => MenuItemTreeHandler::ROOT_PARENT_VALUE,
                        'state' => [
                            'opened' => true
                        ],
                    ],
                    [
                        'entity' => 'menu_item.4_5',
                        'parent' => 'menu_item.4',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                    [
                        'entity' => 'menu_item.4_5_6',
                        'parent' => 'menu_item.4_5',
                        'state' => [
                            'opened' => false
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @param array $expectedData
     * @param $root
     * @param $includeRoot
     */
    protected function assertTreeCreated(array $expectedData, $root, $includeRoot)
    {
        $expectedData = array_reduce($expectedData, function ($result, $data) {
            /** @var MenuItem $entity */
            $entity = $this->getReference($data['entity']);
            $data['id'] = $entity->getId();
            $data['text'] = $entity->getDefaultTitle();
            if ($data['parent'] !== MenuItemTreeHandler::ROOT_PARENT_VALUE) {
                $data['parent'] = $this->getReference($data['parent'])->getId();
            }
            unset($data['entity']);
            $result[$data['id']] = $data;
            return $result;
        }, []);

        $actualTree = $this->handler->createTree($root, $includeRoot);
        $actualTree = array_reduce($actualTree, function ($result, $data) {
            $result[$data['id']] = $data;
            return $result;
        }, []);
        ksort($expectedData);
        ksort($actualTree);
        $this->assertEquals($expectedData, $actualTree);
    }
}

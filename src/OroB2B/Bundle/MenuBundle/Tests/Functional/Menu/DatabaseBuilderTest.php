<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Functional\Menu;

use Knp\Menu\Util\MenuManipulator;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseBuilder;

/**
 * @dbIsolation
 */
class DatabaseBuilderTest extends WebTestCase
{
    /**
     * @var DatabaseBuilder
     */
    protected $builder;

    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures(['OroB2B\Bundle\MenuBundle\Tests\Functional\DataFixtures\LoadMenuItemData']);

        $container = $this->getContainer();

        $this->builder = new DatabaseBuilder(
            $container->get('doctrine'),
            $container->get('orob2b_menu.menu.factory')
        );
    }

    /**
     * @dataProvider buildDataProvider
     * @param string $alias
     * @param string $locale
     * @param array $expectedData
     */
    public function testBuild($alias, $locale, array $expectedData)
    {
        $options = [];
        if ($locale) {
            $options['extras'][MenuItem::LOCALE_OPTION] = $this->getReference($locale);
        }
        $menuItem = $this->builder->build($alias, $options);
        $actualData = (new MenuManipulator())->toArray($menuItem);
        $this->assertEquals($this->prepareExpectedData($expectedData), $actualData);
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            [
                'alias' => 'menu_item.1',
                'locale' => null,
                'expectedData' => [
                    'name' => 'menu_item.1',
                    'label' => 'menu_item.1',
                    'uri' => null,
                    'children' => [
                        'menu_item.1_2' => [
                            'name' => 'menu_item.1_2',
                            'label' => 'menu_item.1_2',
                        ],
                        'menu_item.1_3' => [
                            'name' => 'menu_item.1_3',
                            'label' => 'menu_item.1_3',
                        ],
                    ],
                ],
            ],
            [
                'alias' => 'menu_item.1',
                'locale' => 'en_CA',
                'expectedData' => [
                    'name' => 'menu_item.1',
                    'label' => 'menu_item.1',
                    'uri' => null,
                    'children' => [
                        'menu_item.1_2' => [
                            'name' => 'menu_item.1_2',
                            'label' => 'menu_item.1_2.en_CA',
                        ],
                        'menu_item.1_3' => [
                            'name' => 'menu_item.1_3',
                            'label' => 'menu_item.1_3.en_CA',
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * @dataProvider isSupportedDataProvider
     * @param string $alias
     * @param bool $expectedSupport
     */
    public function testIsSupported($alias, $expectedSupport)
    {
        $this->assertEquals($expectedSupport, $this->builder->isSupported($alias));
    }

    /**
     * @return array
     */
    public function isSupportedDataProvider()
    {
        return [
            [
                'alias' => 'menu_item.1',
                'expectedSupport' => true,
            ],
            [
                'alias' => 'not exists',
                'expectedSupport' => false,
            ]
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    protected function prepareExpectedData(array $data)
    {
        $data = array_merge($this->getDefaultItem(), $data);
        foreach ($data['children'] as &$child) {
            $child = $this->prepareExpectedData($child);
        }
        return $data;
    }

    /**
     * @return array
     */
    protected function getDefaultItem()
    {
        return [
            'uri' => '#',
            'attributes' => [],
            'labelAttributes' => [],
            'linkAttributes' => [],
            'childrenAttributes' => [],
            'extras' => ['isAllowed' => true],
            'display' => true,
            'displayChildren' => true,
            'current' => null,
            'children' => [],
        ];
    }
}

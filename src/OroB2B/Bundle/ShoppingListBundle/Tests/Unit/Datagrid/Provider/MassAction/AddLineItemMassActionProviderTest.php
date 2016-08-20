<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Provider\MassAction;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction\AddLineItemMassActionProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class AddLineItemMassActionProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    /** @var AddLineItemMassActionProvider */
    protected $provider;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($label) {
                return $label;
            });

        $this->provider = new AddLineItemMassActionProvider($this->manager, $this->translator);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->manager, $this->translator);
    }

    /**
     * @dataProvider getActionsDataProvider
     *
     * @param array $shoppingLists
     * @param array $expected
     */
    public function testGetActions(array $shoppingLists, array $expected)
    {
        $this->manager->expects($this->once())
            ->method('getShoppingLists')
            ->willReturn($shoppingLists);

        $this->assertEquals($expected, $this->provider->getActions());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getActionsDataProvider()
    {
        return [
            'no shopping lists' => [
                'shoppingLists' => [],
                'expected' => [
                    'new' => [
                        'type' => 'window',
                        'label' => 'oro.shoppinglist.product.create_new_shopping_list.label',
                        'icon' => 'plus',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'orob2b_shopping_list.mass_action.add_products_handler',
                        'route' => 'orob2b_shopping_list_add_products_to_new_massaction',
                        'frontend_options' => [
                            'title' => 'oro.shoppinglist.product.add_to_shopping_list.label',
                            'regionEnabled' => false,
                            'incrementalPosition' => false,
                            'dialogOptions' => [
                                'modal' => true,
                                'resizable' => false,
                                'width' => 480,
                                'autoResize' => true,
                                'dialogClass' => 'shopping-list-dialog'
                            ],
                            'alias' => 'add_products_to_new_shopping_list_mass_action',
                        ],
                    ]
                ]
            ],
            'shopping lists without current' => [
                'shoppingLists' => [
                    $this->createShoppingList(1),
                    $this->createShoppingList(2),
                ],
                'expected' => [
                    'list1' => [
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'orob2b_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 1
                        ]
                    ],
                    'list2' => [
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'orob2b_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 2
                        ]
                    ],
                    'new' => [
                        'type' => 'window',
                        'label' => 'oro.shoppinglist.product.create_new_shopping_list.label',
                        'icon' => 'plus',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'orob2b_shopping_list.mass_action.add_products_handler',
                        'route' => 'orob2b_shopping_list_add_products_to_new_massaction',
                        'frontend_options' => [
                            'title' => 'oro.shoppinglist.product.add_to_shopping_list.label',
                            'regionEnabled' => false,
                            'incrementalPosition' => false,
                            'dialogOptions' => [
                                'modal' => true,
                                'resizable' => false,
                                'width' => 480,
                                'autoResize' => true,
                                'dialogClass' => 'shopping-list-dialog'
                            ],
                            'alias' => 'add_products_to_new_shopping_list_mass_action',
                        ],
                    ]
                ]
            ],
            'shopping lists with current' => [
                'shoppingLists' => [
                    $this->createShoppingList(42, true),
                    $this->createShoppingList(3),
                ],
                'expected' => [
                    'current' => [
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'orob2b_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 42
                        ]
                    ],
                    'list3' => [
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'orob2b_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 3
                        ]
                    ],
                    'new' => [
                        'type' => 'window',
                        'label' => 'oro.shoppinglist.product.create_new_shopping_list.label',
                        'icon' => 'plus',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'orob2b_shopping_list.mass_action.add_products_handler',
                        'route' => 'orob2b_shopping_list_add_products_to_new_massaction',
                        'frontend_options' => [
                            'title' => 'oro.shoppinglist.product.add_to_shopping_list.label',
                            'regionEnabled' => false,
                            'incrementalPosition' => false,
                            'dialogOptions' => [
                                'modal' => true,
                                'resizable' => false,
                                'width' => 480,
                                'autoResize' => true,
                                'dialogClass' => 'shopping-list-dialog'
                            ],
                            'alias' => 'add_products_to_new_shopping_list_mass_action',
                        ],
                    ]
                ]
            ]
        ];
    }

    /**
     * @param int $id
     * @param bool $isCurrent
     * @return ShoppingList
     */
    protected function createShoppingList($id, $isCurrent = false)
    {
        return $this->getEntity(
            'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList',
            [
                'id' => $id,
                'label' => 'shopping_list_' . $id,
                'organization' => new Organization(),
                'account' => new Account(),
                'accountUser' => new AccountUser(),
                'current' => $isCurrent
            ]
        );
    }
}

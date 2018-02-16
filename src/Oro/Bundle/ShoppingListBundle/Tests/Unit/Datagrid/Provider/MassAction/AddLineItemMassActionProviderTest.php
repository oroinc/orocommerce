<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Provider\MassAction;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction\AddLineItemMassActionProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Component\Testing\Unit\EntityTrait;

class AddLineItemMassActionProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface */
    protected $tokenStorage;

    /** @var AddLineItemMassActionProvider */
    protected $provider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FeatureChecker */
    protected $featureChecker;

    protected function setUp()
    {
        $this->manager = $this->createMock(ShoppingListManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($label) {
                return $label;
            });

        $this->provider = new AddLineItemMassActionProvider($this->manager, $this->translator, $this->tokenStorage);
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature('shopping_list_create');
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
     * @param bool  $isGuest
     * @param bool  $isShoppingListCreateFeatureEnabled
     */
    public function testGetActions(array $shoppingLists, array $expected, $isGuest, $isShoppingListCreateFeatureEnabled)
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn($isShoppingListCreateFeatureEnabled);

        /** @var AbstractToken $token */
        $token = $this->createMock(
            $isGuest ?
                AnonymousCustomerUserToken::class :
                UsernamePasswordOrganizationToken::class
        );

        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        if ($isGuest) {
            $this->manager->expects($this->never())
                ->method('getShoppingLists');
        } else {
            $this->manager->expects($this->once())
                ->method('getShoppingLists')
                ->willReturn($shoppingLists);
        }

        $this->assertEquals($expected, $this->provider->getActions());
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getActionsDataProvider()
    {
        return [
            'no shopping lists and registered customer' => [
                'shoppingLists' => [],
                'expected' => [
                    'new' => [
                        'is_current' => false,
                        'type' => 'window',
                        'label' => 'oro.shoppinglist.product.create_new_shopping_list.label',
                        'icon' => 'plus',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route' => 'oro_shopping_list_add_products_to_new_massaction',
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
                        'frontend_handle' => 'shopping-list-create',
                    ]
                ],
                'isGuest' => false,
                'isShoppingListCreateFeatureEnabled' => true
            ],
            'no shopping lists, registered customer and feature disabled' => [
                'shoppingLists' => [],
                'expected' => [],
                'isGuest' => false,
                'isShoppingListCreateFeatureEnabled' => false
            ],
            'shopping lists, registered customer' => [
                'shoppingLists' => [
                    $this->createShoppingList(1),
                    $this->createShoppingList(2),
                ],
                'expected' => [
                    'list1' => [
                        'is_current' => false,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 1
                        ]
                    ],
                    'list2' => [
                        'is_current' => false,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 2
                        ]
                    ],
                    'new' => [
                        'is_current' => false,
                        'type' => 'window',
                        'label' => 'oro.shoppinglist.product.create_new_shopping_list.label',
                        'icon' => 'plus',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route' => 'oro_shopping_list_add_products_to_new_massaction',
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
                        'frontend_handle' => 'shopping-list-create',
                    ]
                ],
                'isGuest' => false,
                'isShoppingListCreateFeatureEnabled' => true
            ],
            'shopping lists, registered customer and feature disabled' => [
                'shoppingLists' => [
                    $this->createShoppingList(1),
                    $this->createShoppingList(2),
                ],
                'expected' => [
                    'list1' => [
                        'is_current' => false,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 1
                        ]
                    ],
                    'list2' => [
                        'is_current' => false,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 2
                        ]
                    ]
                ],
                'isGuest' => false,
                'isShoppingListCreateFeatureEnabled' => false
            ],
            'shopping lists, guest customer' => [
                'shoppingLists' => [
                    $this->createShoppingList(42, true),
                ],
                'expected' => [
                    'current' => [
                        'is_current' => true,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_current_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                    ],
                    'new' => [
                        'is_current' => false,
                        'type' => 'window',
                        'label' => 'oro.shoppinglist.product.create_new_shopping_list.label',
                        'icon' => 'plus',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route' => 'oro_shopping_list_add_products_to_new_massaction',
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
                        'frontend_handle' => 'shopping-list-create',
                    ]
                ],
                'isGuest' => true,
                'isShoppingListCreateFeatureEnabled' => true
            ],
            'shopping lists, guest customer and feature disabled' => [
                'shoppingLists' => [
                    $this->createShoppingList(42, true),
                ],
                'expected' => [
                    'current' => [
                        'is_current' => true,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_current_shopping_list',
                        'icon' => 'shopping-cart',
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                    ]
                ],
                'isGuest' => true,
                'isShoppingListCreateFeatureEnabled' => false
            ]
        ];
    }

    public function testGetActionsCreateShoppingListFeatureOff()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $shoppingLists = [
            $this->createShoppingList(1),
            $this->createShoppingList(2),
        ];

        $this->manager->expects($this->once())
            ->method('getShoppingLists')
            ->willReturn($shoppingLists);

        $this->assertArrayNotHasKey('new', $this->provider->getActions());
    }

    public function testGetActionsForAnonymousCustomerUser()
    {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        /** @var AnonymousCustomerUserToken $token */
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->manager
            ->expects($this->never())
            ->method('getShoppingLists');

        $expectedActions = [
            'current' => [
                'is_current' => true,
                'type' => 'addproducts',
                'label' => 'oro.shoppinglist.actions.add_to_current_shopping_list',
                'icon' => 'shopping-cart',
                'data_identifier' => 'product.id',
                'frontend_type' => 'add-products-mass',
                'handler' => 'oro_shopping_list.mass_action.add_products_handler'
            ]
        ];

        $this->assertEquals($expectedActions, $this->provider->getActions());
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
                'customer' => new Customer(),
                'customerUser' => new CustomerUser(),
                'current' => $isCurrent
            ]
        );
    }
}

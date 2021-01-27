<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Provider\MassAction;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction\AddLineItemMassActionProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddLineItemMassActionProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CurrentShoppingListManager
     */
    protected $currentShoppingListManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AddLineItemMassActionProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|FeatureChecker
     */
    protected $featureChecker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    private $configManager;

    protected function setUp(): void
    {
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($label) {
                return $label;
            });

        $this->provider = new AddLineItemMassActionProvider(
            $this->currentShoppingListManager,
            $this->translator,
            $this->tokenStorage,
            $this->authorizationChecker,
            $this->configManager
        );
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature('shopping_list_create');
    }

    /**
     * @dataProvider getActionsDataProvider
     *
     * @param array $shoppingLists
     * @param array $expected
     * @param bool  $isGuest
     * @param bool  $isShoppingListCreateFeatureEnabled
     * @param bool  $editAllowed
     * @param bool  $createAllowed
     */
    public function testGetActions(
        array $shoppingLists,
        array $expected,
        $isGuest,
        $isShoppingListCreateFeatureEnabled,
        $editAllowed,
        $createAllowed
    ) {
        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->willReturn($isShoppingListCreateFeatureEnabled);

        if ($isGuest) {
            $token = $this->createMock(AnonymousCustomerUserToken::class);
        } else {
            $token = $this->createMock(UsernamePasswordOrganizationToken::class);
            $token->expects($this->any())
                ->method('getUser')
                ->willReturn(new CustomerUser());
        }

        $this->tokenStorage
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker
            ->method('isGranted')
            ->withConsecutive(
                ['oro_shopping_list_frontend_update'],
                ['oro_shopping_list_frontend_create']
            )
            ->willReturnOnConsecutiveCalls(
                $editAllowed,
                $createAllowed
            );

        if ($isGuest || !$editAllowed) {
            $this->currentShoppingListManager->expects($this->never())
                ->method('getShoppingLists');
        } elseif ($token->getUser()) {
            $this->currentShoppingListManager->expects($this->once())
                ->method('getShoppingListsByCustomerUser')
                ->with($this->isInstanceOf(CustomerUser::class), ['list.id' => Criteria::ASC])
                ->willReturn($shoppingLists);

            $this->configManager->expects($this->any())
                ->method('get')
                ->with('oro_shopping_list.show_all_in_shopping_list_widget')
                ->willReturn(false);
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
                'isShoppingListCreateFeatureEnabled' => true,
                'editAllowed' => true,
                'createAllowed' => true,
            ],
            'no shopping lists, registered customer and feature disabled' => [
                'shoppingLists' => [],
                'expected' => [],
                'isGuest' => false,
                'isShoppingListCreateFeatureEnabled' => false,
                'editAllowed' => false,
                'createAllowed' => false,
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
                'isShoppingListCreateFeatureEnabled' => true,
                'editAllowed' => true,
                'createAllowed' => true,
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
                'isShoppingListCreateFeatureEnabled' => false,
                'editAllowed' => true,
                'createAllowed' => false,
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
                'isShoppingListCreateFeatureEnabled' => true,
                'editAllowed' => true,
                'createAllowed' => true,
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
                'isShoppingListCreateFeatureEnabled' => false,
                'editAllowed' => true,
                'createAllowed' => false,
            ],
            'feature enabled without user permission' => [
                'shoppingLists' => [],
                'expected' => [],
                'isGuest' => false,
                'isShoppingListCreateFeatureEnabled' => true,
                'editAllowed' => false,
                'createAllowed' => false,
            ],
            'user permission without feature enabled' => [
                'shoppingLists' => [],
                'expected' => [],
                'isGuest' => false,
                'isShoppingListCreateFeatureEnabled' => false,
                'editAllowed' => false,
                'createAllowed' => true,
            ],
            'feature enabled without guest permission' => [
                'shoppingLists' => [],
                'expected' => [],
                'isGuest' => true,
                'isShoppingListCreateFeatureEnabled' => true,
                'editAllowed' => false,
                'createAllowed' => false,
            ],
            'guest permission without feature enabled' => [
                'shoppingLists' => [],
                'expected' => [],
                'isGuest' => true,
                'isShoppingListCreateFeatureEnabled' => false,
                'editAllowed' => false,
                'createAllowed' => true,
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
                'customer' => new Customer(),
                'customerUser' => new CustomerUser(),
                'current' => $isCurrent
            ]
        );
    }
}

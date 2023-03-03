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
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddLineItemMassActionProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currentShoppingListManager;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var AddLineItemMassActionProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function ($label) {
                return $label;
            });

        $this->provider = new AddLineItemMassActionProvider(
            $this->currentShoppingListManager,
            $translator,
            $this->tokenStorage,
            $this->authorizationChecker,
            $this->configManager
        );
        $this->provider->setFeatureChecker($this->featureChecker);
        $this->provider->addFeature('shopping_list_create');
    }

    private function getShoppingList(int $id, bool $isCurrent = false): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);
        $shoppingList->setLabel('shopping_list_' . $id);
        $shoppingList->setOrganization(new Organization());
        $shoppingList->setCustomer(new Customer());
        $shoppingList->setCustomerUser(new CustomerUser());
        $shoppingList->setCurrent($isCurrent);

        return $shoppingList;
    }

    /**
     * @dataProvider getActionsDataProvider
     */
    public function testGetActions(
        array $shoppingLists,
        array $expected,
        bool $isGuest,
        bool $isShoppingListCreateFeatureEnabled,
        bool $editAllowed,
        bool $createAllowed
    ): void {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn($isShoppingListCreateFeatureEnabled);

        if ($isGuest) {
            $token = $this->createMock(AnonymousCustomerUserToken::class);
        } else {
            $token = $this->createMock(UsernamePasswordOrganizationToken::class);
            $token->expects(self::any())
                ->method('getUser')
                ->willReturn(new CustomerUser());
        }

        $this->tokenStorage->expects(self::any())
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker->expects(self::any())
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
            $this->currentShoppingListManager->expects(self::never())
                ->method('getShoppingLists');
        } elseif ($token->getUser()) {
            $this->currentShoppingListManager->expects(self::once())
                ->method('getShoppingListsByCustomerUser')
                ->with($this->isInstanceOf(CustomerUser::class), ['list.id' => Criteria::ASC])
                ->willReturn($shoppingLists);

            $this->currentShoppingListManager->expects(self::once())
                ->method('getCurrent')
                ->with(false, '')
                ->willReturn(end($shoppingLists));

            $this->configManager->expects(self::any())
                ->method('get')
                ->with('oro_shopping_list.show_all_in_shopping_list_widget')
                ->willReturn(false);
        }

        self::assertEquals($expected, $this->provider->getActions());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getActionsDataProvider(): array
    {
        return [
            'no shopping lists and registered customer' => [
                'shoppingLists' => [],
                'expected' => [
                    'new' => [
                        'is_current' => false,
                        'type' => 'window',
                        'label' => 'oro.shoppinglist.product.create_new_shopping_list.label',
                        'translatable' => false,
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
                        'launcherOptions' => [
                            'iconClassName' => 'fa-plus',
                        ],
                        'attributes' => [
                            'data-order' => 'new',
                        ],
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
                    $this->getShoppingList(1),
                    $this->getShoppingList(2, true),
                ],
                'expected' => [
                    'list1' => [
                        'is_current' => false,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'translatable' => false,
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 1
                        ],
                        'launcherOptions' => [
                            'iconClassName' => 'fa-shopping-cart',
                        ],
                        'attributes' => [
                            'data-order' => 'add',
                        ],
                    ],
                    'list2' => [
                        'is_current' => true,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'translatable' => false,
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 2
                        ],
                        'launcherOptions' => [
                            'iconClassName' => 'fa-shopping-cart',
                        ],
                        'attributes' => [
                            'data-order' => 'new',
                        ],
                    ],
                    'new' => [
                        'is_current' => false,
                        'type' => 'window',
                        'label' => 'oro.shoppinglist.product.create_new_shopping_list.label',
                        'translatable' => false,
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
                        'launcherOptions' => [
                            'iconClassName' => 'fa-plus',
                        ],
                        'attributes' => [
                            'data-order' => 'new',
                        ],
                    ]
                ],
                'isGuest' => false,
                'isShoppingListCreateFeatureEnabled' => true,
                'editAllowed' => true,
                'createAllowed' => true,
            ],
            'shopping lists, registered customer and feature disabled' => [
                'shoppingLists' => [
                    $this->getShoppingList(1),
                    $this->getShoppingList(2, true),
                ],
                'expected' => [
                    'list1' => [
                        'is_current' => false,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'translatable' => false,
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 1
                        ],
                        'launcherOptions' => [
                            'iconClassName' => 'fa-shopping-cart',
                        ],
                        'attributes' => [
                            'data-order' => 'add',
                        ],
                    ],
                    'list2' => [
                        'is_current' => true,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_shopping_list',
                        'translatable' => false,
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'route_parameters' => [
                            'shoppingList' => 2
                        ],
                        'launcherOptions' => [
                            'iconClassName' => 'fa-shopping-cart',
                        ],
                        'attributes' => [
                            'data-order' => 'new',
                        ],
                    ]
                ],
                'isGuest' => false,
                'isShoppingListCreateFeatureEnabled' => false,
                'editAllowed' => true,
                'createAllowed' => false,
            ],
            'shopping lists, guest customer' => [
                'shoppingLists' => [
                    $this->getShoppingList(42, true),
                ],
                'expected' => [
                    'current' => [
                        'is_current' => true,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_current_shopping_list',
                        'translatable' => false,
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'launcherOptions' => [
                            'iconClassName' => 'fa-shopping-cart',
                        ],
                    ],
                    'new' => [
                        'is_current' => false,
                        'type' => 'window',
                        'label' => 'oro.shoppinglist.product.create_new_shopping_list.label',
                        'translatable' => false,
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
                        'launcherOptions' => [
                            'iconClassName' => 'fa-plus',
                        ],
                        'attributes' => [
                            'data-order' => 'new',
                        ],
                    ]
                ],
                'isGuest' => true,
                'isShoppingListCreateFeatureEnabled' => true,
                'editAllowed' => true,
                'createAllowed' => true,
            ],
            'shopping lists, guest customer and feature disabled' => [
                'shoppingLists' => [
                    $this->getShoppingList(42, true),
                ],
                'expected' => [
                    'current' => [
                        'is_current' => true,
                        'type' => 'addproducts',
                        'label' => 'oro.shoppinglist.actions.add_to_current_shopping_list',
                        'translatable' => false,
                        'data_identifier' => 'product.id',
                        'frontend_type' => 'add-products-mass',
                        'handler' => 'oro_shopping_list.mass_action.add_products_handler',
                        'launcherOptions' => [
                            'iconClassName' => 'fa-shopping-cart',
                        ],
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
}

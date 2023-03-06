<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides mass actions available for shopping list line items.
 */
class AddLineItemMassActionProvider implements MassActionProviderInterface
{
    const NAME_PREFIX = 'oro_shoppinglist_frontend_addlineitem';

    use FeatureCheckerHolderTrait;

    /**
     * @var CurrentShoppingListManager
     */
    protected $currentShoppingListManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var ConfigManager|null
     */
    private $configManager;

    /**
     * @var boolean|null
     */
    private $isShowAllShoppingLists;

    public function __construct(
        CurrentShoppingListManager $currentShoppingListManager,
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        ConfigManager $configManager
    ) {
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        $actions = [];

        if ($this->isEditAllowed()) {
            if ($this->isGuestCustomerUser()) {
                $actions['current'] = $this->getConfig([
                    'label' => $this->translator->trans('oro.shoppinglist.actions.add_to_current_shopping_list'),
                    'translatable' => false,
                    'is_current' => true
                ]);
            } else {
                $user = $this->getCustomerUser();
                if ($user && !$this->isShowAllInShoppingListWidget()) {
                    $shoppingLists = $this->currentShoppingListManager->getShoppingListsByCustomerUser(
                        $user,
                        ['list.id' => Criteria::ASC]
                    );
                } else {
                    $shoppingLists = $this->currentShoppingListManager->getShoppingLists(['list.id' => Criteria::ASC]);
                }

                $currentShoppingList = $this->currentShoppingListManager->getCurrent();

                foreach ($shoppingLists as $shoppingList) {
                    $name = 'list' . $shoppingList->getId();

                    $actions[$name] = $this->getConfig([
                        'label' => $this->getLabel($shoppingList),
                        'translatable' => false,
                        'route_parameters' => [
                            'shoppingList' => $shoppingList->getId(),
                        ],
                        'is_current' => $currentShoppingList?->getId() === $shoppingList->getId(),
                        'attributes' => [
                            'data-order' => $shoppingList->isCurrent() ? 'new' : 'add'
                        ]
                    ]);
                }
            }
        }

        if ($this->isFeaturesEnabled() && $this->isCreateAllowed()) {
            $actions['new'] = $this->getConfig([
                'type' => 'window',
                'label' => $this->translator->trans('oro.shoppinglist.product.create_new_shopping_list.label'),
                'translatable' => false,
                'route' => 'oro_shopping_list_add_products_to_new_massaction',
                'frontend_handle' => 'shopping-list-create',
                'frontend_options' => [
                    'title' => $this->translator->trans('oro.shoppinglist.product.add_to_shopping_list.label'),
                    'regionEnabled' => false,
                    'incrementalPosition' => false,
                    'dialogOptions' => [
                        'modal' => true,
                        'resizable' => false,
                        'width' => 480,
                        'autoResize' => true,
                        'dialogClass' => 'shopping-list-dialog',
                    ],
                    'alias' => 'add_products_to_new_shopping_list_mass_action',
                ],
                'launcherOptions' => [
                    'iconClassName' => 'fa-plus'
                ],
                'attributes' => [
                    'data-order' => 'new'
                ]
            ]);
        }

        return $actions;
    }

    /**
     * @return array
     */
    public function getFormattedActions()
    {
        $massActions = $this->getActions();
        $formattedMassActions = [];
        foreach ($massActions as $title => $massAction) {
            $formattedMassActions[self::NAME_PREFIX . $title] = $massAction;
        }

        return $formattedMassActions;
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getConfig($options)
    {
        return array_merge([
            'type' => 'addproducts',
            'data_identifier' => 'product.id',
            'frontend_type' => 'add-products-mass',
            'handler' => 'oro_shopping_list.mass_action.add_products_handler',
            'is_current' => false,
            'launcherOptions' => [
                'iconClassName' => 'fa-shopping-cart'
            ]
        ], $options);
    }

    /**
     * @param ShoppingList $shoppingList
     * @return string
     */
    protected function getLabel(ShoppingList $shoppingList)
    {
        return $this->translator->trans(
            'oro.shoppinglist.actions.add_to_shopping_list',
            [
                '{{ shoppingList }}' => \strip_tags($shoppingList->getLabel())
            ]
        );
    }

    private function isGuestCustomerUser(): bool
    {
        return $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken;
    }

    private function getCustomerUser(): ?CustomerUser
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();

        return $user instanceof CustomerUser ? $user : null;
    }

    private function isCreateAllowed(): bool
    {
        return $this->authorizationChecker->isGranted('oro_shopping_list_frontend_create');
    }

    private function isEditAllowed(): bool
    {
        return $this->authorizationChecker->isGranted('oro_shopping_list_frontend_update');
    }

    private function isShowAllInShoppingListWidget(): bool
    {
        if ($this->isShowAllShoppingLists === null) {
            $this->isShowAllShoppingLists = $this->configManager
                ->get('oro_shopping_list.show_all_in_shopping_list_widget');
        }

        return $this->isShowAllShoppingLists;
    }
}

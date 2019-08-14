<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;
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
     * @param CurrentShoppingListManager $currentShoppingListManager
     * @param TranslatorInterface $translator
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        CurrentShoppingListManager $currentShoppingListManager,
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
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
                    'is_current' => true
                ]);
            } else {
                $shoppingLists = $this->currentShoppingListManager->getShoppingLists(['list.id' => Criteria::ASC]);

                /** @var ShoppingList $shoppingList */
                foreach ($shoppingLists as $shoppingList) {
                    $name = 'list' . $shoppingList->getId();

                    $actions[$name] = $this->getConfig([
                        'label' => $this->getLabel($shoppingList),
                        'route_parameters' => [
                            'shoppingList' => $shoppingList->getId(),
                        ],
                    ]);
                }
            }
        }

        if ($this->isFeaturesEnabled() && $this->isCreateAllowed()) {
            $actions['new'] = $this->getConfig([
                'type' => 'window',
                'label' => $this->translator->trans('oro.shoppinglist.product.create_new_shopping_list.label'),
                'icon' => 'plus',
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
            'icon' => 'shopping-cart',
            'data_identifier' => 'product.id',
            'frontend_type' => 'add-products-mass',
            'handler' => 'oro_shopping_list.mass_action.add_products_handler',
            'is_current' => false
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

    /**
     * @return bool
     */
    private function isGuestCustomerUser(): bool
    {
        return $this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken;
    }

    /**
     * @return bool
     */
    private function isCreateAllowed(): bool
    {
        return $this->authorizationChecker->isGranted('oro_shopping_list_frontend_create');
    }

    /**
     * @return bool
     */
    private function isEditAllowed(): bool
    {
        return $this->authorizationChecker->isGranted('oro_shopping_list_frontend_update');
    }
}

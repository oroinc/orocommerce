<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class AddLineItemMassActionProvider implements MassActionProviderInterface
{
    /** @var ShoppingListManager */
    protected $manager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ShoppingListManager $manager
     * @param TranslatorInterface $translator
     */
    public function __construct(ShoppingListManager $manager, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        $actions = [];

        /** @var ShoppingList $shoppingList */
        foreach ($this->manager->getShoppingLists() as $shoppingList) {
            $name = $shoppingList->isCurrent() ? 'current' : 'list' . $shoppingList->getId();

            $actions[$name] = $this->getConfig([
                'label' => $this->getLabel($shoppingList),
                'route_parameters' => [
                    'shoppingList' => $shoppingList->getId(),
                ],
            ]);
        }

        $actions['new'] = $this->getConfig([
            'type' => 'window',
            'label' => $this->translator->trans('oro.shoppinglist.product.create_new_shopping_list.label'),
            'icon' => 'plus',
            'route' => 'orob2b_shopping_list_add_products_to_new_massaction',
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

        return $actions;
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
            'handler' => 'orob2b_shopping_list.mass_action.add_products_handler',
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
                '{{ shoppingList }}' => $shoppingList->getLabel()
            ]
        );
    }
}

<?php

namespace OroB2B\Bundle\ShoppingListBundle\Datagrid\Provider\MassAction;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderInterface;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

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
        foreach ($this->getShoppingLists() as $shoppingList) {
            $name = $shoppingList->isCurrent() ? 'current' : 'list' . $shoppingList->getId();

            $actions[$name] = $this->getConfig($shoppingList);
        }

        $actions['new'] = [
            'type' => 'addproductstonew',
            'label' => $this->translator->trans('orob2b.shoppinglist.product.create_new_shopping_list.label'),
            'icon' => 'plus',
            'data_identifier' => 'product.id'
        ];

        return $actions;
    }

    /**
     * @return array
     */
    protected function getShoppingLists()
    {
        $shoppingLists = $this->manager->getShoppingLists();

        if ($shoppingLists['currentShoppingList']) {
            array_unshift($shoppingLists['shoppingLists'], $shoppingLists['currentShoppingList']);
        }

        return $shoppingLists['shoppingLists'];
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    protected function getConfig(ShoppingList $shoppingList)
    {
        return [
            'type' => 'addproducts',
            'label' => $this->getLabel($shoppingList),
            'icon' => 'shopping-cart',
            'data_identifier' => 'product.id',
            'route_parameters' => [
                'shoppingList' => $shoppingList->getId()
            ],
        ];
    }

    /**
     * @param ShoppingList $shoppingList
     * @return string
     */
    protected function getLabel(ShoppingList $shoppingList)
    {
        return $this->translator->trans(
            'orob2b.shoppinglist.actions.add_to_shopping_list',
            [
                '{{ shoppingList }}' => $shoppingList->getLabel()
            ]
        );
    }
}

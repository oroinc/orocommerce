<?php

namespace OroB2B\Bundle\ShoppingListBundle\Provider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class ShoppingListForm implements DataProviderInterface
{
    /**
     * @var FormAccessor[]
     */
    protected $data = [];

    /**
     * @var FormInterface[]
     */
    protected $form = [];

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(
        FormFactoryInterface $formFactory
    ) {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_shopping_list_shopping_list_form';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $shoppingList = $context->data()->get('shoppingList');
        $shoppingListId = $shoppingList->getId();

        if (!isset($this->data[$shoppingListId])) {
            $this->data[$shoppingListId] = new FormAccessor(
                $this->getForm($shoppingList)
            );
        }
        return $this->data[$shoppingListId];
    }

    /**
     * @param ShoppingList $shoppingList
     * @return FormInterface
     */
    public function getForm(ShoppingList $shoppingList)
    {
        $shoppingListId = $shoppingList->getId();

        if (!isset($this->form[$shoppingListId])) {
            $this->form[$shoppingListId] = $this->formFactory
                ->create(ShoppingListType::NAME, $shoppingList);
        }
        return $this->form[$shoppingListId];
    }
}

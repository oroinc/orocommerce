<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractDataProvider;
use Oro\Bundle\FormBundle\Form\Handler\FormProviderInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAction;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class ShoppingListFormProvider extends AbstractDataProvider implements FormProviderInterface
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
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $shoppingList = $context->data()->get('shoppingList');
        $shoppingListId = $shoppingList->getId();

        if (!isset($this->data[$shoppingListId])) {
            if ($shoppingListId) {
                $action = FormAction::createByRoute('orob2b_shopping_list_frontend_update', ['id' => $shoppingListId]);
            } else {
                $action = FormAction::createByRoute('orob2b_shopping_list_frontend_create');
            }
            $this->data[$shoppingListId] = new FormAccessor(
                $this->getForm($shoppingList),
                $action
            );
        }
        return $this->data[$shoppingListId];
    }

    /**
     * @param ShoppingList|null $shoppingList
     * @param array $options
     * @return FormInterface
     */
    public function getForm($shoppingList = null, array $options = [])
    {
        if (!$shoppingList instanceof ShoppingList) {
            throw new \InvalidArgumentException(
                sprintf(
                    'shoppingList should be instance of ShoppingList, instance of %s given.',
                    is_object($shoppingList) ? get_class($shoppingList) : gettype($shoppingList)
                )
            );
        }
        $shoppingListId = $shoppingList->getId();

        if (!isset($this->form[$shoppingListId])) {
            $this->form[$shoppingListId] = $this->formFactory
                ->create(ShoppingListType::NAME, $shoppingList, $options);
        }
        return $this->form[$shoppingListId];
    }
}

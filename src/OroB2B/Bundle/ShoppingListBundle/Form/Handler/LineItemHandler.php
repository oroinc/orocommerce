<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

class LineItemHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var Registry */
    protected $registry;

    /** @var ShoppingListManager */
    protected $shoppingListManager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param Registry $registry
     * @param ShoppingListManager $shoppingListManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        Registry $registry,
        ShoppingListManager $shoppingListManager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->registry = $registry;
        $this->shoppingListManager = $shoppingListManager;
    }

    /**
     * @param LineItem $lineItem
     *
     * @return bool
     */
    public function process(LineItem $lineItem)
    {
        if (!in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            return false;
        }
        /** @var EntityManagerInterface $manager */
        $manager = $this->registry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
        $manager->beginTransaction();

        // handle case for new shopping list creation
        $formName = $this->form->getName();
        $formData = $this->request->request->get($formName, []);
        if (empty($formData['shoppingList']) && !empty($formData['shoppingListLabel'])) {
            $shoppingList = $this->shoppingListManager->createCurrent($formData['shoppingListLabel']);
            $formData['shoppingList'] = $shoppingList->getId();
            $this->request->request->set($formName, $formData);
        }

        $this->form->submit($this->request);
        if ($this->form->isValid()) {
            $this->shoppingListManager->addLineItem($lineItem, $lineItem->getShoppingList(), true, true);
            $manager->commit();
            return true;
        }
        $manager->rollback();
        return false;
    }
}

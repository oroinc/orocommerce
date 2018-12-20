<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles add a product to a shopping list request.
 */
class LineItemHandler
{
    use RequestHandlerTrait;

    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ShoppingListManager */
    protected $shoppingListManager;

    /** @var CurrentShoppingListManager */
    protected $currentShoppingListManager;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ManagerRegistry $doctrine
     * @param ShoppingListManager $shoppingListManager
     * @param CurrentShoppingListManager $currentShoppingListManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ManagerRegistry $doctrine,
        ShoppingListManager $shoppingListManager,
        CurrentShoppingListManager $currentShoppingListManager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->doctrine = $doctrine;
        $this->shoppingListManager = $shoppingListManager;
        $this->currentShoppingListManager = $currentShoppingListManager;
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

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(LineItem::class);
        $em->beginTransaction();

        // handle case for new shopping list creation
        $formName = $this->form->getName();
        $formData = $this->request->request->get($formName, []);
        if (empty($formData['shoppingList']) && !empty($formData['shoppingListLabel'])) {
            $shoppingList = $this->currentShoppingListManager->createCurrent($formData['shoppingListLabel']);
            $formData['shoppingList'] = $shoppingList->getId();
            $this->request->request->set($formName, $formData);
        }

        $this->submitPostPutRequest($this->form, $this->request);
        if ($this->form->isValid()) {
            $this->shoppingListManager->addLineItem($lineItem, $lineItem->getShoppingList(), true, true);
            $em->commit();

            return true;
        }

        $em->rollback();

        return false;
    }
}

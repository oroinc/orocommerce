<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class LineItemCollectionHandler
{
    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ShoppingListManager
     */
    private $shoppingListManager;

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
     * @return bool
     */
    public function process():bool
    {
        if (!in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            return false;
        }
        $manager = $this->registry->getManagerForClass('OroShoppingListBundle:LineItem');
        $manager->beginTransaction();
        $this->form->submit($this->request);
        if ($this->form->isValid()) {
            /** @var ShoppingList $shoppingList */
            $shoppingList = $this->form->getData();
            $lineItems = $shoppingList->getLineItems();
            if (!$lineItems->isEmpty()) {
                foreach ($lineItems as $lineItem) {
                    $lineItem->setShoppingList($shoppingList);
                    $lineItem->setCustomerUser($shoppingList->getCustomerUser());
                    $lineItem->setOrganization($shoppingList->getOrganization());
                    $this->shoppingListManager->addLineItem($lineItem, $shoppingList, true, true);
                }
                $manager->commit();
                return true;
            }
        }
        $manager->rollback();
        return false;
    }
}

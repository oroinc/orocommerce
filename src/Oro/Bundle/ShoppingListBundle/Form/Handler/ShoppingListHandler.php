<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles create a shopping list request.
 */
class ShoppingListHandler
{
    use RequestHandlerTrait;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var CurrentShoppingListManager
     */
    protected $currentShoppingListManager;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    public function __construct(
        FormInterface $form,
        Request $request,
        CurrentShoppingListManager $currentShoppingListManager,
        ManagerRegistry $doctrine
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->doctrine = $doctrine;
    }

    /**
     * @param ShoppingList $shoppingList
     *
     * @return bool
     */
    public function process(ShoppingList $shoppingList)
    {
        $this->form->setData($shoppingList);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($this->form, $this->request);
            if ($this->form->isValid()) {
                $em = $this->doctrine->getManagerForClass(ShoppingList::class);
                if ($shoppingList->getId() === null) {
                    $em->persist($shoppingList);
                    $em->flush();
                    $this->currentShoppingListManager->setCurrent(
                        $shoppingList->getCustomerUser(),
                        $shoppingList
                    );
                } else {
                    $em->persist($shoppingList);
                    $em->flush();
                }

                return true;
            }
        }

        return false;
    }
}

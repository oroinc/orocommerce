<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles create a shopping list request.
 */
class ShoppingListHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    protected CurrentShoppingListManager $currentShoppingListManager;
    protected ManagerRegistry $doctrine;

    public function __construct(
        CurrentShoppingListManager $currentShoppingListManager,
        ManagerRegistry $doctrine
    ) {
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function process($shoppingList, FormInterface $form, Request $request)
    {
        $form->setData($shoppingList);

        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($form, $request);
            if ($form->isValid()) {
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

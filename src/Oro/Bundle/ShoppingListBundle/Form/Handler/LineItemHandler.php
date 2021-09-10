<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Handles add a product to a shopping list request.
 */
class LineItemHandler
{
    use RequestHandlerTrait;

    /** @var FormInterface */
    private $form;

    /** @var Request */
    private $request;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ShoppingListManager */
    private $shoppingListManager;

    /** @var CurrentShoppingListManager */
    private $currentShoppingListManager;

    /** @var ValidatorInterface */
    private $validator;

    public function __construct(
        FormInterface $form,
        Request $request,
        ManagerRegistry $doctrine,
        ShoppingListManager $shoppingListManager,
        CurrentShoppingListManager $currentShoppingListManager,
        ValidatorInterface $validator
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->doctrine = $doctrine;
        $this->shoppingListManager = $shoppingListManager;
        $this->currentShoppingListManager = $currentShoppingListManager;
        $this->validator = $validator;
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
            $this->shoppingListManager->addLineItem($lineItem, $lineItem->getShoppingList(), false, true);

            if ($this->isShoppingListValid($lineItem->getShoppingList())) {
                $em->flush();
                $em->commit();

                return true;
            }
        }

        $em->rollback();

        return false;
    }

    private function isShoppingListValid(ShoppingList $shoppingList): bool
    {
        $constraintViolationList = $this->validator->validate($shoppingList);

        if ($constraintViolationList->count()) {
            /** @var ConstraintViolation $constraintViolation */
            foreach ($constraintViolationList as $constraintViolation) {
                $this->addFormError($constraintViolation);
            }

            return false;
        }

        return true;
    }

    private function addFormError(ConstraintViolation $constraintViolation): void
    {
        $this->form->addError(new FormError(
            $constraintViolation->getMessage(),
            $constraintViolation->getMessageTemplate(),
            $constraintViolation->getParameters(),
            $constraintViolation->getPlural()
        ));
    }
}

<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ProductBundle\Rounding\QuantityRoundingService;

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

    /** @var int */
    protected $savedId;

    /** @var QuantityRoundingService */
    protected $roundingService;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param Registry $registry
     * @param ShoppingListManager $shoppingListManager
     * @param QuantityRoundingService $roundingService
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        Registry $registry,
        ShoppingListManager $shoppingListManager,
        QuantityRoundingService $roundingService
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->registry = $registry;
        $this->shoppingListManager = $shoppingListManager;
        $this->roundingService = $roundingService;
    }

    /**
     * @param LineItem $lineItem
     *
     * @return bool
     */
    public function process(LineItem $lineItem)
    {
        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
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
                /** @var LineItemRepository $lineItemRepository */
                $lineItemRepository = $manager->getRepository('OroB2BShoppingListBundle:LineItem');
                $existingLineItem = $lineItemRepository->findDuplicate($lineItem);

                if ($existingLineItem) {
                    $this->updateExistingLineItem($lineItem, $existingLineItem);
                } else {
                    $lineItem->setQuantity(
                        $this->roundingService->roundQuantity(
                            $lineItem->getQuantity(),
                            $lineItem->getProductUnit(),
                            $lineItem->getProduct()
                        )
                    );

                    $manager->persist($lineItem);
                }

                $manager->flush();
                $manager->commit();
                $this->shoppingListManager->recalculateSubtotals($lineItem->getShoppingList());

                return true;
            } else {
                $manager->rollback();
            }
        }

        return false;
    }

    /**
     * Update savedId for widget result
     *
     * @param array $result
     *
     * @return array
     */
    public function updateSavedId(array $result)
    {
        if ($this->savedId) {
            $result['savedId'] = $this->savedId;
        }

        return $result;
    }

    /**
     * @param LineItem $lineItem
     * @param LineItem $existingLineItem
     */
    protected function updateExistingLineItem(LineItem $lineItem, LineItem $existingLineItem)
    {
        $newQuantity = $this->roundingService->roundQuantity(
            $lineItem->getQuantity() + $existingLineItem->getQuantity(),
            $existingLineItem->getProductUnit(),
            $existingLineItem->getProduct()
        );

        $existingLineItem->setQuantity($newQuantity);
        $existingLineItemNote = $existingLineItem->getNotes();
        $newNotes = $lineItem->getNotes();
        $notes = trim(implode(' ', [$existingLineItemNote, $newNotes]));
        if ($notes) {
            $existingLineItem->setNotes($notes);
        }
        $this->savedId = $existingLineItem->getId();
    }
}

<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;

class LineItemHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var Registry */
    protected $registry;

    /** @var int */
    protected $savedId;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param Registry      $registry
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        Registry $registry
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->registry = $registry;
    }

    /**
     * @param LineItem $lineItem
     *
     * @return bool
     */
    public function process(LineItem $lineItem)
    {
        $manager = $this->registry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
        /** @var \PDO $connection */
        $connection = $this->registry->getConnection();

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $connection->beginTransaction();
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                /** @var LineItemRepository $lineItemRepository */
                $lineItemRepository = $manager->getRepository('OroB2BShoppingListBundle:LineItem');
                $existingLineItem = $lineItemRepository->findDuplicate($lineItem);

                if ($existingLineItem) {
                    $existingLineItem->setQuantity($lineItem->getQuantity() + $existingLineItem->getQuantity());
                    $existingLineItemNote = $existingLineItem->getNotes();
                    $newNotes = $lineItem->getNotes();
                    $notes = trim(implode(' ', [$existingLineItemNote, $newNotes]));
                    if ($notes) {
                        $existingLineItem->setNotes($notes);
                    }
                    $this->savedId = $existingLineItem->getId();
                } else {
                    $manager->persist($lineItem);
                }

                $manager->flush();
                $connection->commit();

                return true;
            } else {
                $connection->rollBack();
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
}

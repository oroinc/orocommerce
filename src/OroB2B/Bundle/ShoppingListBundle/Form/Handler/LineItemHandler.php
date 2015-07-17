<?php
namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
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

    /** @var ObjectManager */
    protected $manager;

    /** @var ObjectManager */
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
        $this->manager = $registry->getManagerForClass('OroB2BShoppingListBundle:LineItem');
    }

    /**
     * @param LineItem $lineItem
     *
     * @return bool
     */
    public function process(LineItem $lineItem)
    {
        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                /** @var LineItemRepository $lineItemRepository */
                $lineItemRepository = $this->manager->getRepository('OroB2BShoppingListBundle:LineItem');
                $existingLineItem = $lineItemRepository->findDuplicate($lineItem);

                if ($existingLineItem) {
                    $existingLineItem->setQuantity($lineItem->getQuantity() + $existingLineItem->getQuantity());
                    $this->savedId = $existingLineItem->getId();
                } else {
                    $this->manager->persist($lineItem);
                }

                $this->manager->flush();

                return true;
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

<?php
namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class ShoppingListHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var ObjectManager */
    protected $om;

    /** @var ShoppingListManager */
    protected $manager;

    /**
     * @param FormInterface $form
     * @param Request       $request
     * @param ObjectManager $om
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ShoppingListManager $manager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
    }

    public function process(ShoppingList $shoppingList)
    {
        $this->form->setData($shoppingList);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $this->form->submit($this->request);

            if ($this->form->isValid() && $shoppingList->getId() === null) {
                $this->manager->setCurrent(
                    $shoppingList->getAccountUser(),
                    $shoppingList
                );

                return true;
            }
        }

        return false;
    }
}

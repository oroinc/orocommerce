<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class ShoppingListHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ShoppingListManager
     */
    protected $manager;

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @param FormInterface       $form
     * @param Request             $request
     * @param ShoppingListManager $manager
     * @param Registry            $doctrine
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ShoppingListManager $manager,
        Registry $doctrine
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
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
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                if ($shoppingList->getId() === null) {
                    $this->manager->setCurrent(
                        $shoppingList->getAccountUser(),
                        $shoppingList
                    );
                } else {
                    $em = $this->doctrine->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
                    $em->persist($shoppingList);
                    $em->flush();
                }

                return true;
            }
        }

        return false;
    }
}

<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

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

    /** @var ShoppingListManager */
    protected $manager;

    /** @var  EntityManager */
    protected $em;

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
        $this->em = $doctrine->getManagerForClass('OroB2BShoppingListBundle:ShoppingList');
    }

    /**
     * @param ShoppingList $shoppingList
     *
     * @return bool
     */
    public function process(ShoppingList $shoppingList)
    {
        $this->form->setData($shoppingList);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                if ($shoppingList->getId() === null) {
                    $this->manager->setCurrent(
                        $shoppingList->getAccountUser(),
                        $shoppingList
                    );
                } else {
                    $this->em->persist($shoppingList);
                    $this->em->flush();
                }

                return true;
            }
        }

        return false;
    }
}

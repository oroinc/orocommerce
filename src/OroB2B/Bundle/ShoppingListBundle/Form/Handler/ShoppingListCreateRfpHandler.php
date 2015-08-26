<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\DBALException;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListCreateRfpHandler
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
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var AccountUser
     */
    protected $user;

    /**
     * @var RFPRequest
     */
    protected $rfpRequest;

    /**
     * @var DBALException
     */
    protected $exception;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param ObjectManager $manager
     * @param AccountUser $user
     */
    public function __construct(FormInterface $form, Request $request, ObjectManager $manager, AccountUser $user)
    {
        $this->form = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->user = $user;
    }

    /**
     * @return RFPRequest
     */
    public function getRfpRequest()
    {
        return $this->rfpRequest;
    }

    /**
     * @return DBALException
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return boolean
     */
    public function process(ShoppingList $shoppingList)
    {
        $this->form->setData($shoppingList);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                return $this->onSuccess($shoppingList);
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param ShoppingList $entity
     * @return bool
     */
    protected function onSuccess(ShoppingList $entity)
    {
        $rfpRequest = new RFPRequest();
        $rfpRequest
            //->setShoppingList($entity)
                ->setFirstName($this->user->getFirstName())
                ->setLastName($this->user->getLastName())
                ->setEmail($this->user->getEmail())
                ->setPhone('')
                ->setRole('')
                ->setBody('')
                ->setCompany($this->user->getOrganization()->getName())
            ->setAccountUser($this->user)
            ->setAccount($this->user->getAccount())
        ;

        foreach ($entity->getLineItems() as $shoppingListLineItem) {
            $requestProduct = new RequestProduct();
            $requestProduct
                ->setProduct($shoppingListLineItem->getProduct())
            ;
            $requestProductItem = new RequestProductItem();
            $requestProductItem
                //->setFromShoppingList(true)
                ->setQuantity($shoppingListLineItem->getQuantity())
                ->setProductUnit($shoppingListLineItem->getUnit())
                //->setShoppingListProductOffer($shoppingListProductOffer)
                //->setPriceType($shoppingListProductOffer->getPriceType())
            ;
            $requestProduct->addRequestProductItem($requestProductItem);
            $rfpRequest->addRequestProduct($requestProduct);
        }

        try {
            $this->manager->persist($rfpRequest);
            $this->manager->flush();

            $this->rfpRequest = $rfpRequest;
        } catch (DBALException $e) {
            $this->exception = $e;

            return false;
        }

        return true;
    }
}

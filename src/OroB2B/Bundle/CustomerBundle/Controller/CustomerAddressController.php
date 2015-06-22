<?php

namespace OroB2B\Bundle\CustomerBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerTypedAddressType;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class CustomerAddressController extends Controller
{
    /**
     * @Route("/address-book/{id}", name="orob2b_customer_address_book", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_customer_view")
     *
     * @param Customer $customer
     * @return array
     */
    public function addressBookAction(Customer $customer)
    {
        return [
            'entity' => $customer,
            'address_edit_acl_resource' => 'orob2b_customer_update'
        ];
    }

    /**
     * @Route(
     *      "/{customerId}/address-create",
     *      name="orob2b_customer_address_create",
     *      requirements={"customerId"="\d+"}
     * )
     * @Template("OroB2BCustomerBundle:CustomerAddress:update.html.twig")
     * @AclAncestor("orob2b_customer_create")
     * @ParamConverter("customer", options={"id" = "customerId"})
     *
     * @param Customer $customer
     * @return array
     */
    public function createAction(Customer $customer)
    {
        return $this->update($customer, new CustomerAddress());
    }

    /**
     * @Route(
     *      "/{customerId}/address-update/{id}",
     *      name="orob2b_customer_address_update",
     *      requirements={"customerId"="\d+","id"="\d+"},defaults={"id"=0}
     * )
     * @Template
     * @AclAncestor("orob2b_customer_update")
     * @ParamConverter("customer", options={"id" = "customerId"})
     *
     * @param Customer        $customer
     * @param CustomerAddress $address
     * @return array
     */
    public function updateAction(Customer $customer, CustomerAddress $address)
    {
        return $this->update($customer, $address);
    }

    /**
     * @param Customer $customer
     * @param CustomerAddress $address
     * @return array
     * @throws BadRequestHttpException
     */
    protected function update(Customer $customer, CustomerAddress $address)
    {
        $responseData = [
            'saved' => false,
            'customer' => $customer
        ];

        if ($this->getRequest()->getMethod() == 'GET' && !$address->getId()) {
            if (!$customer->getAddresses()->count()) {
                $address->setPrimary(true);
            }
        }

        if ($address->getOwner() && $address->getOwner()->getId() != $customer->getId()) {
            throw new BadRequestHttpException('Address must belong to customer');
        } elseif (!$address->getOwner()) {
            $customer->addAddress($address);
        }

        $form = $this->createForm(CustomerTypedAddressType::NAME, $address);

        $handler = new AddressHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BCustomerBundle:CustomerAddress')
        );

        if ($handler->process($address)) {
            $this->getDoctrine()->getManager()->flush();
            $responseData['entity'] = $address;
            $responseData['saved'] = true;
        }

        $responseData['form'] = $form->createView();
        return $responseData;
    }
}

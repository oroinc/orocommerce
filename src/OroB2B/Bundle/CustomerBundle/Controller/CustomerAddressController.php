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
     * @Template("OroB2BCustomerBundle:Addresses/widget:addressBook.html.twig")
     * @AclAncestor("orob2b_customer_view")
     *
     * @param Customer $customer
     * @return array
     */
    public function addressBookAction(Customer $customer)
    {
        return [
            'entity' => $customer,
            'address_edit_acl_resource' => 'orob2b_customer_update',
            'options' => $this->getAddressBookOptions($customer)
        ];
    }

    /**
     * @Route(
     *      "/{entityId}/address-create",
     *      name="orob2b_customer_address_create",
     *      requirements={"entityId"="\d+"}
     * )
     * @Template("OroB2BCustomerBundle:Addresses/widget:update.html.twig")
     * @AclAncestor("orob2b_customer_create")
     * @ParamConverter("customer", options={"id" = "entityId"})
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
     *      "/{entityId}/address-update/{id}",
     *      name="orob2b_customer_address_update",
     *      requirements={"entityId"="\d+","id"="\d+"},defaults={"id"=0}
     * )
     * @Template("OroB2BCustomerBundle:Addresses/widget:update.html.twig")
     * @AclAncestor("orob2b_customer_update")
     * @ParamConverter("customer", options={"id" = "entityId"})
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
            'entity' => $customer
        ];

        if ($this->getRequest()->getMethod() === 'GET' && !$address->getId()) {
            if (!$customer->getAddresses()->count()) {
                $address->setPrimary(true);
            }
        }

        if (!$address->getOwner()) {
            $customer->addAddress($address);
        } elseif ($address->getOwner()->getId() != $customer->getId()) {
            throw new BadRequestHttpException('Address must belong to Customer');
        }

        $form = $this->createForm(CustomerTypedAddressType::NAME, $address);

        $handler = new AddressHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('orob2b_customer.entity.customer_address.class')
            )
        );

        if ($handler->process($address)) {
            $this->getDoctrine()->getManager()->flush();
            $responseData['entity'] = $address;
            $responseData['saved'] = true;
        }

        $responseData['form'] = $form->createView();
        $responseData['routes'] = [
            'create' => 'orob2b_customer_address_create',
            'update' => 'orob2b_customer_address_update'
        ];
        return $responseData;
    }

    /**
     * @param Customer $entity
     * @return array
     */
    protected function getAddressBookOptions($entity)
    {
        $addressListUrl = $this->generateUrl('orob2b_api_customer_get_customer_addresses', [
            'entityId' => $entity->getId()
        ]);

        $addressCreateUrl = $this->generateUrl('orob2b_customer_address_create', [
            'entityId' => $entity->getId()
        ]);

        $currentAddresses = $this->get('fragment.handler')->render($addressListUrl);

        return [
            'wid'                    => $this->getRequest()->get('_wid'),
            'entityId'               => $entity->getId(),
            'addressListUrl'         => $addressListUrl,
            'addressCreateUrl'       => $addressCreateUrl,
            'addressUpdateRouteName' => 'orob2b_customer_address_update',
            'currentAddresses'       => $currentAddresses
        ];
    }
}

<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerTypedAddressType;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;

class CustomerAddressController extends Controller
{
    /**
     * @Route("/address-book/{id}", name="oro_customer_address_book", requirements={"id"="\d+"})
     * @Template("OroCustomerBundle:Address/widget:addressBook.html.twig")
     * @AclAncestor("oro_customer_customer_view")
     *
     * @param Customer $customer
     * @return array
     */
    public function addressBookAction(Customer $customer)
    {
        return [
            'entity' => $customer,
            'address_edit_acl_resource' => 'oro_customer_customer_update',
            'options' => $this->getAddressBookOptions($customer)
        ];
    }

    /**
     * @Route(
     *      "/{entityId}/address-create",
     *      name="oro_customer_address_create",
     *      requirements={"entityId"="\d+"}
     * )
     * @Template("OroCustomerBundle:Address/widget:update.html.twig")
     * @AclAncestor("oro_customer_customer_create")
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
     *      name="oro_customer_address_update",
     *      requirements={"entityId"="\d+","id"="\d+"},defaults={"id"=0}
     * )
     * @Template("OroCustomerBundle:Address/widget:update.html.twig")
     * @AclAncestor("oro_customer_customer_update")
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

        if ($this->getRequest()->getMethod() === 'GET' && !$address->getId() && !$customer->getAddresses()->count()) {
            $address->setPrimary(true);
        }

        if (!$address->getFrontendOwner()) {
            $customer->addAddress($address);
        } elseif ($address->getFrontendOwner()->getId() !== $customer->getId()) {
            throw new BadRequestHttpException('Address must belong to Customer');
        }

        $form = $this->createForm(CustomerTypedAddressType::NAME, $address);

        $handler = new AddressHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('oro_customer.entity.customer_address.class')
            )
        );

        if ($handler->process($address)) {
            $this->getDoctrine()->getManager()->flush();
            $responseData['entity'] = $address;
            $responseData['saved'] = true;
        }

        $responseData['form'] = $form->createView();
        $responseData['routes'] = [
            'create' => 'oro_customer_address_create',
            'update' => 'oro_customer_address_update'
        ];
        return $responseData;
    }

    /**
     * @param Customer $entity
     * @return array
     */
    protected function getAddressBookOptions($entity)
    {
        $addressListUrl = $this->generateUrl('oro_api_customer_get_commercecustomer_addresses', [
            'entityId' => $entity->getId()
        ]);

        $addressCreateUrl = $this->generateUrl('oro_customer_address_create', [
            'entityId' => $entity->getId()
        ]);

        $currentAddresses = $this->get('fragment.handler')->render($addressListUrl);

        return [
            'wid'                    => $this->getRequest()->get('_wid'),
            'entityId'               => $entity->getId(),
            'addressListUrl'         => $addressListUrl,
            'addressCreateUrl'       => $addressCreateUrl,
            'addressUpdateRouteName' => 'oro_customer_address_update',
            'currentAddresses'       => $currentAddresses
        ];
    }
}

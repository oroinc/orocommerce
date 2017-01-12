<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserTypedAddressType;

class CustomerUserAddressController extends Controller
{
    /**
     * @Route("/address-book/{id}", name="oro_customer_customer_user_address_book", requirements={"id"="\d+"})
     * @Template("OroCustomerBundle:Address/widget:addressBook.html.twig")
     * @AclAncestor("oro_customer_customer_user_view")
     *
     * @param CustomerUser $customerUser
     * @return array
     */
    public function addressBookAction(CustomerUser $customerUser)
    {
        return [
            'entity' => $customerUser,
            'address_edit_acl_resource' => 'oro_customer_customer_user_update',
            'options' => $this->getAddressBookOptions($customerUser)
        ];
    }

    /**
     * @Route(
     *      "/{entityId}/address-create",
     *      name="oro_customer_customer_user_address_create",
     *      requirements={"customerUserId"="\d+"}
     * )
     * @Template("OroCustomerBundle:Address/widget:update.html.twig")
     * @AclAncestor("oro_customer_customer_user_create")
     * @ParamConverter("customerUser", options={"id" = "entityId"})
     *
     * @param CustomerUser $customerUser
     * @return array
     */
    public function createAction(CustomerUser $customerUser)
    {
        return $this->update($customerUser, new CustomerUserAddress());
    }

    /**
     * @Route(
     *      "/{entityId}/address-update/{id}",
     *      name="oro_customer_customer_user_address_update",
     *      requirements={"customerUserId"="\d+","id"="\d+"},defaults={"id"=0}
     * )
     * @Template("OroCustomerBundle:Address/widget:update.html.twig")
     * @AclAncestor("oro_customer_customer_user_update")
     * @ParamConverter("customerUser", options={"id" = "entityId"})
     *
     * @param CustomerUser        $customerUser
     * @param CustomerUserAddress $address
     * @return array
     */
    public function updateAction(CustomerUser $customerUser, CustomerUserAddress $address)
    {
        return $this->update($customerUser, $address);
    }

    /**
     * @param CustomerUser $customerUser
     * @param CustomerUserAddress $address
     * @return array
     * @throws BadRequestHttpException
     */
    protected function update(CustomerUser $customerUser, CustomerUserAddress $address)
    {
        $responseData = [
            'saved' => false,
            'entity' => $customerUser
        ];

        if ($this->getRequest()->getMethod() === 'GET' && !$address->getId()) {
            $address->setFirstName($customerUser->getFirstName());
            $address->setLastName($customerUser->getLastName());
            if (!$customerUser->getAddresses()->count()) {
                $address->setPrimary(true);
            }
        }

        if (!$address->getFrontendOwner()) {
            $customerUser->addAddress($address);
        } elseif ($address->getFrontendOwner()->getId() !== $customerUser->getId()) {
            throw new BadRequestHttpException('Address must belong to CustomerUser');
        }

        $form = $this->createForm(CustomerUserTypedAddressType::NAME, $address);

        $manager = $this->getDoctrine()->getManagerForClass(
            $this->container->getParameter('oro_customer.entity.customer_user_address.class')
        );

        $handler = new AddressHandler($form, $this->getRequest(), $manager);

        if ($handler->process($address)) {
            $this->getDoctrine()->getManager()->flush();
            $responseData['entity'] = $address;
            $responseData['saved'] = true;
        }

        $responseData['form'] = $form->createView();
        $responseData['routes'] = [
            'create' => 'oro_customer_customer_user_address_create',
            'update' => 'oro_customer_customer_user_address_update'
        ];
        return $responseData;
    }

    /**
     * @param CustomerUser $entity
     * @return array
     */
    protected function getAddressBookOptions($entity)
    {
        $addressListUrl = $this->generateUrl('oro_api_customer_get_customeruser_addresses', [
            'entityId' => $entity->getId()
        ]);

        $addressCreateUrl = $this->generateUrl('oro_customer_customer_user_address_create', [
            'entityId' => $entity->getId()
        ]);

        $currentAddresses = $this->get('fragment.handler')->render($addressListUrl);

        return [
            'wid'                    => $this->getRequest()->get('_wid'),
            'entityId'               => $entity->getId(),
            'addressListUrl'         => $addressListUrl,
            'addressCreateUrl'       => $addressCreateUrl,
            'addressUpdateRouteName' => 'oro_customer_customer_user_address_update',
            'currentAddresses'       => $currentAddresses
        ];
    }
}

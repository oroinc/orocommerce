<?php

namespace OroB2B\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserTypedAddressType;

class AccountUserAddressController extends Controller
{
    /**
     * @Route("/address-book/{id}", name="orob2b_customer_account_user_address_book", requirements={"id"="\d+"})
     * @Template("OroB2BCustomerBundle:Addresses/widget:addressBook.html.twig")
     * @AclAncestor("orob2b_customer_account_user_view")
     *
     * @param AccountUser $accountUser
     * @return array
     */
    public function addressBookAction(AccountUser $accountUser)
    {
        return [
            'entity' => $accountUser,
            'address_edit_acl_resource' => 'orob2b_customer_account_user_update',
            'options' => $this->getAddressBookOptions($accountUser)
        ];
    }

    /**
     * @Route(
     *      "/{entityId}/address-create",
     *      name="orob2b_customer_account_user_address_create",
     *      requirements={"accountUserId"="\d+"}
     * )
     * @Template("OroB2BCustomerBundle:Addresses/widget:update.html.twig")
     * @AclAncestor("orob2b_customer_account_user_create")
     * @ParamConverter("accountUser", options={"id" = "entityId"})
     *
     * @param AccountUser $accountUser
     * @return array
     */
    public function createAction(AccountUser $accountUser)
    {
        return $this->update($accountUser, new AccountUserAddress());
    }

    /**
     * @Route(
     *      "/{entityId}/address-update/{id}",
     *      name="orob2b_customer_account_user_address_update",
     *      requirements={"accountUserId"="\d+","id"="\d+"},defaults={"id"=0}
     * )
     * @Template("OroB2BCustomerBundle:Addresses/widget:update.html.twig")
     * @AclAncestor("orob2b_customer_account_user_update")
     * @ParamConverter("accountUser", options={"id" = "entityId"})
     *
     * @param AccountUser        $accountUser
     * @param AccountUserAddress $address
     * @return array
     */
    public function updateAction(AccountUser $accountUser, AccountUserAddress $address)
    {
        return $this->update($accountUser, $address);
    }

    /**
     * @param AccountUser $accountUser
     * @param AccountUserAddress $address
     * @return array
     * @throws BadRequestHttpException
     */
    protected function update(AccountUser $accountUser, AccountUserAddress $address)
    {
        $responseData = [
            'saved' => false,
            'entity' => $accountUser
        ];

        if ($this->getRequest()->getMethod() == 'GET' && !$address->getId()) {
            $address->setFirstName($accountUser->getFirstName());
            $address->setLastName($accountUser->getLastName());
            if (!$accountUser->getAddresses()->count()) {
                $address->setPrimary(true);
            }
        }

        if ($address->getOwner() && $address->getOwner()->getId() != $accountUser->getId()) {
            throw new BadRequestHttpException('Address must belong to AccountUser');
        } elseif (!$address->getOwner()) {
            $accountUser->addAddress($address);
        }

        $form = $this->createForm(AccountUserTypedAddressType::NAME, $address);

        $handler = new AddressHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('orob2b_customer.entity.account_user_address.class')
            )
        );

        if ($handler->process($address)) {
            $this->getDoctrine()->getManager()->flush();
            $responseData['entity'] = $address;
            $responseData['saved'] = true;
        }

        $responseData['form'] = $form->createView();
        $responseData['routes'] = [
            'create' => 'orob2b_customer_account_user_address_create',
            'update' => 'orob2b_customer_account_user_address_update'
        ];
        return $responseData;
    }

    /**
     * @param AccountUser $entity
     * @return array
     */
    protected function getAddressBookOptions($entity)
    {
        $addressListUrl = $this->generateUrl('orob2b_api_customer_account_user_get_accountuser_addresses', [
            'entityId' => $entity->getId()
        ]);

        $addressCreateUrl = $this->generateUrl('orob2b_customer_account_user_address_create', [
                'entityId' => $entity->getId()
        ]);

        $currentAddresses = $this->get('fragment.handler')->render($addressListUrl);

        return [
            'wid'                    => $this->getRequest()->get('_wid'),
            'entityId'               => $entity->getId(),
            'addressListUrl'         => $addressListUrl,
            'addressCreateUrl'       => $addressCreateUrl,
            'addressUpdateRouteName' => 'orob2b_customer_account_user_address_update',
            'currentAddresses'       => $currentAddresses
        ];
    }
}

<?php

namespace OroB2B\Bundle\CustomerBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\CustomerBundle\Form\Type\CustomerTypedAddressType;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class AccountUserAddressController extends Controller
{
    /**
     * @Route("/address-book/{id}", name="orob2b_customer_account_user_address_book", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_customer_account_user_view")
     *
     * @param AccountUser $accountUser
     * @return array
     */
    public function addressBookAction(AccountUser $accountUser)
    {
        return [
            'entity' => $accountUser,
            'address_edit_acl_resource' => 'orob2b_customer_account_user_update'
        ];
    }

    /**
     * @Route(
     *      "/{accountUserId}/address-create",
     *      name="orob2b_customer_account_user_address_create",
     *      requirements={"accountUserId"="\d+"}
     * )
     * @Template("OroB2BCustomerBundle:AccountUserAddress:update.html.twig")
     * @AclAncestor("orob2b_customer_account_user_create")
     * @ParamConverter("accountUser", options={"id" = "accountUserId"})
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
     *      "/{accountUserId}/address-update/{id}",
     *      name="orob2b_customer_account_user_address_update",
     *      requirements={"accountUserId"="\d+","id"="\d+"},defaults={"id"=0}
     * )
     * @Template
     * @AclAncestor("orob2b_customer_account_user_update")
     * @ParamConverter("accountUser", options={"id" = "accountUserId"})
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
            'accountUser' => $accountUser
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

        $form = $this->createForm(CustomerTypedAddressType::NAME, $address, [
            'data_class' => 'OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress'
        ]);

        $handler = new AddressHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass('OroB2BCustomerBundle:AccountUserAddress')
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

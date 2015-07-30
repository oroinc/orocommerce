<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountTypedAddressType;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class AccountAddressController extends Controller
{
    /**
     * @Route("/address-book/{id}", name="orob2b_account_address_book", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:Address/widget:addressBook.html.twig")
     * @AclAncestor("orob2b_account_view")
     *
     * @param Account $account
     * @return array
     */
    public function addressBookAction(Account $account)
    {
        return [
            'entity' => $account,
            'address_edit_acl_resource' => 'orob2b_account_update',
            'options' => $this->getAddressBookOptions($account)
        ];
    }

    /**
     * @Route(
     *      "/{entityId}/address-create",
     *      name="orob2b_account_address_create",
     *      requirements={"entityId"="\d+"}
     * )
     * @Template("OroB2BAccountBundle:Address/widget:update.html.twig")
     * @AclAncestor("orob2b_account_create")
     * @ParamConverter("account", options={"id" = "entityId"})
     *
     * @param Account $account
     * @return array
     */
    public function createAction(Account $account)
    {
        return $this->update($account, new AccountAddress());
    }

    /**
     * @Route(
     *      "/{entityId}/address-update/{id}",
     *      name="orob2b_account_address_update",
     *      requirements={"entityId"="\d+","id"="\d+"},defaults={"id"=0}
     * )
     * @Template("OroB2BAccountBundle:Address/widget:update.html.twig")
     * @AclAncestor("orob2b_account_update")
     * @ParamConverter("account", options={"id" = "entityId"})
     *
     * @param Account        $account
     * @param AccountAddress $address
     * @return array
     */
    public function updateAction(Account $account, AccountAddress $address)
    {
        return $this->update($account, $address);
    }

    /**
     * @param Account $account
     * @param AccountAddress $address
     * @return array
     * @throws BadRequestHttpException
     */
    protected function update(Account $account, AccountAddress $address)
    {
        $responseData = [
            'saved' => false,
            'entity' => $account
        ];

        if ($this->getRequest()->getMethod() === 'GET' && !$address->getId() && !$account->getAddresses()->count()) {
            $address->setPrimary(true);
        }

        if (!$address->getOwner()) {
            $account->addAddress($address);
        } elseif ($address->getOwner()->getId() != $account->getId()) {
            throw new BadRequestHttpException('Address must belong to Account');
        }

        $form = $this->createForm(AccountTypedAddressType::NAME, $address);

        $handler = new AddressHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('orob2b_account.entity.account_address.class')
            )
        );

        if ($handler->process($address)) {
            $this->getDoctrine()->getManager()->flush();
            $responseData['entity'] = $address;
            $responseData['saved'] = true;
        }

        $responseData['form'] = $form->createView();
        $responseData['routes'] = [
            'create' => 'orob2b_account_address_create',
            'update' => 'orob2b_account_address_update'
        ];
        return $responseData;
    }

    /**
     * @param Account $entity
     * @return array
     */
    protected function getAddressBookOptions($entity)
    {
        $addressListUrl = $this->generateUrl('orob2b_api_account_get_account_addresses', [
            'entityId' => $entity->getId()
        ]);

        $addressCreateUrl = $this->generateUrl('orob2b_account_address_create', [
            'entityId' => $entity->getId()
        ]);

        $currentAddresses = $this->get('fragment.handler')->render($addressListUrl);

        return [
            'wid'                    => $this->getRequest()->get('_wid'),
            'entityId'               => $entity->getId(),
            'addressListUrl'         => $addressListUrl,
            'addressCreateUrl'       => $addressCreateUrl,
            'addressUpdateRouteName' => 'orob2b_account_address_update',
            'currentAddresses'       => $currentAddresses
        ];
    }
}

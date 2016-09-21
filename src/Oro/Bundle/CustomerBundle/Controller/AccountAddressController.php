<?php

namespace Oro\Bundle\CustomerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\AddressBundle\Form\Handler\AddressHandler;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Form\Type\AccountTypedAddressType;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;

class AccountAddressController extends Controller
{
    /**
     * @Route("/address-book/{id}", name="oro_account_address_book", requirements={"id"="\d+"})
     * @Template("OroCustomerBundle:Address/widget:addressBook.html.twig")
     * @AclAncestor("oro_account_view")
     *
     * @param Account $account
     * @return array
     */
    public function addressBookAction(Account $account)
    {
        return [
            'entity' => $account,
            'address_edit_acl_resource' => 'oro_account_update',
            'options' => $this->getAddressBookOptions($account)
        ];
    }

    /**
     * @Route(
     *      "/{entityId}/address-create",
     *      name="oro_account_address_create",
     *      requirements={"entityId"="\d+"}
     * )
     * @Template("OroCustomerBundle:Address/widget:update.html.twig")
     * @AclAncestor("oro_account_create")
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
     *      name="oro_account_address_update",
     *      requirements={"entityId"="\d+","id"="\d+"},defaults={"id"=0}
     * )
     * @Template("OroCustomerBundle:Address/widget:update.html.twig")
     * @AclAncestor("oro_account_update")
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

        if (!$address->getFrontendOwner()) {
            $account->addAddress($address);
        } elseif ($address->getFrontendOwner()->getId() !== $account->getId()) {
            throw new BadRequestHttpException('Address must belong to Account');
        }

        $form = $this->createForm(AccountTypedAddressType::NAME, $address);

        $handler = new AddressHandler(
            $form,
            $this->getRequest(),
            $this->getDoctrine()->getManagerForClass(
                $this->container->getParameter('oro_account.entity.account_address.class')
            )
        );

        if ($handler->process($address)) {
            $this->getDoctrine()->getManager()->flush();
            $responseData['entity'] = $address;
            $responseData['saved'] = true;
        }

        $responseData['form'] = $form->createView();
        $responseData['routes'] = [
            'create' => 'oro_account_address_create',
            'update' => 'oro_account_address_update'
        ];
        return $responseData;
    }

    /**
     * @param Account $entity
     * @return array
     */
    protected function getAddressBookOptions($entity)
    {
        $addressListUrl = $this->generateUrl('oro_api_account_get_account_addresses', [
            'entityId' => $entity->getId()
        ]);

        $addressCreateUrl = $this->generateUrl('oro_account_address_create', [
            'entityId' => $entity->getId()
        ]);

        $currentAddresses = $this->get('fragment.handler')->render($addressListUrl);

        return [
            'wid'                    => $this->getRequest()->get('_wid'),
            'entityId'               => $entity->getId(),
            'addressListUrl'         => $addressListUrl,
            'addressCreateUrl'       => $addressCreateUrl,
            'addressUpdateRouteName' => 'oro_account_address_update',
            'currentAddresses'       => $currentAddresses
        ];
    }
}

<?php

namespace OroB2B\Bundle\CustomerBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\CustomerBundle\Form\Handler\AccountUserHandler;

class AccountUserController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_customer_account_user_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_customer_account_user_view",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUser",
     *      permission="VIEW"
     * )
     *
     * @param AccountUser $accountUser
     * @return array
     */
    public function viewAction(AccountUser $accountUser)
    {
        return [
            'entity' => $accountUser
        ];
    }

    /**
     * @Route("/", name="orob2b_customer_account_user_index")
     * @Template
     * @AclAncestor("orob2b_customer_account_user_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_customer.entity.account_user.class')
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_customer_account_user_info", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("orob2b_customer_account_user_view")
     *
     * @param AccountUser $accountUser
     * @return array
     */
    public function infoAction(AccountUser $accountUser)
    {
        return [
            'entity' => $accountUser
        ];
    }

    /**
     * Create account user form
     *
     * @Route("/create", name="orob2b_customer_account_user_create")
     * @Template("OroB2BCustomerBundle:AccountUser:update.html.twig")
     * @Acl(
     *      id="orob2b_customer_account_user_create",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUser",
     *      permission="CREATE"
     * )
     * @return array|RedirectResponse
     */
    public function createAction()
    {
        return $this->update(new AccountUser());
    }

    /**
     * Edit account user form
     *
     * @Route("/update/{id}", name="orob2b_customer_account_user_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_customer_account_user_update",
     *      type="entity",
     *      class="OroB2BCustomerBundle:AccountUser",
     *      permission="EDIT"
     * )
     * @param AccountUser $accountUser
     * @return array|RedirectResponse
     */
    public function updateAction(AccountUser $accountUser)
    {
        return $this->update($accountUser);
    }

    /**
     * @param AccountUser $accountUser
     * @return array|RedirectResponse
     */
    protected function update(AccountUser $accountUser)
    {
        $form = $this->createForm(AccountUserType::NAME, $accountUser);
        $handler = new AccountUserHandler(
            $form,
            $this->getRequest(),
            $this->get('orob2b_account_user.manager'),
            $this->get('security.context')
        );

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            function (AccountUser $accountUser) {
                return [
                    'route' => 'orob2b_customer_account_user_update',
                    'parameters' => ['id' => $accountUser->getId()]
                ];
            },
            function (AccountUser $accountUser) {
                return [
                    'route' => 'orob2b_customer_account_user_view',
                    'parameters' => ['id' => $accountUser->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.customer.controller.accountuser.saved.message'),
            $handler
        );

        return $result;
    }
}

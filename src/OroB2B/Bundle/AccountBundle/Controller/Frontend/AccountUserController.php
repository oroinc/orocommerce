<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\LayoutBundle\Annotation\Layout;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserHandler;

class AccountUserController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_account_frontend_account_user_view", requirements={"id"="\d+"})
     * @Layout
     * @Acl(
     *      id="orob2b_account_frontend_account_user_view",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUser",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @param AccountUser $accountUser
     * @return array
     */
    public function viewAction(AccountUser $accountUser)
    {
        return [
            'data' => [
                'entity' => $accountUser
            ]
        ];
    }

    /**
     * @Route("/", name="orob2b_account_frontend_account_user_index")
     * @Layout(vars={"entity_class"})
     * @AclAncestor("orob2b_account_frontend_account_user_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_account.entity.account_user.class')
        ];
    }

    /**
     * Create account user form
     *
     * @Route("/create", name="orob2b_account_frontend_account_user_create")
     * @Layout
     * @Acl(
     *      id="orob2b_account_frontend_account_user_create",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUser",
     *      permission="CREATE",
     *      group_name="commerce"
     * )
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        return $this->update(new AccountUser(), $request);
    }

    /**
     * Edit account user form
     *
     * @Route("/update/{id}", name="orob2b_account_frontend_account_user_update", requirements={"id"="\d+"})
     * @Layout
     * @Acl(
     *      id="orob2b_account_frontend_account_user_update",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUser",
     *      permission="EDIT",
     *      group_name="commerce"
     * )
     * @param AccountUser $accountUser
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function updateAction(AccountUser $accountUser, Request $request)
    {
        return  $this->update($accountUser, $request);
    }

    /**
     * @param AccountUser $accountUser
     * @param Request $request
     * @return array|RedirectResponse
     */
    protected function update(AccountUser $accountUser, Request $request)
    {
        $form = $this->get('orob2b_account.provider.frontend_account_user_form')->getForm($accountUser);
        $handler = new AccountUserHandler(
            $form,
            $request,
            $this->get('orob2b_account_user.manager'),
            $this->get('oro_security.security_facade'),
            $this->get('translator'),
            $this->get('logger')
        );
        if (!$accountUser->getOwner()) {
            $user = $accountUser->getAccount()->getOwner();
            $accountUser->setOwner($user);
        }
        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            function (AccountUser $accountUser) {
                return [
                    'route' => 'orob2b_account_frontend_account_user_update',
                    'parameters' => ['id' => $accountUser->getId()]
                ];
            },
            function (AccountUser $accountUser) {
                return [
                    'route' => 'orob2b_account_frontend_account_user_view',
                    'parameters' => ['id' => $accountUser->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.account.controller.accountuser.saved.message'),
            $handler
        );

        if ($result instanceof Response) {
            return $result;
        }

        return [
            'data' => [
                'entity' => $accountUser
            ]
        ];
    }
}

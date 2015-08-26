<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserHandler;
use OroB2B\Bundle\AccountBundle\Form\Type\FrontendAccountUserType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;


class AccountUserController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_account_frontend_account_user_view", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountUser/Frontend:view.html.twig")
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
            'entity' => $accountUser
        ];
    }

    /**
     * @Route("/", name="orob2b_account_frontend_account_user_index")
     * @Template("OroB2BAccountBundle:AccountUser/Frontend:index.html.twig")
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
     * @Template("OroB2BAccountBundle:AccountUser/Frontend:update.html.twig")
     * @Acl(
     *      id="orob2b_account_frontend_account_user_create",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUser",
     *      permission="CREATE",
     *      group_name="commerce"
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
     * @Route("/update/{id}", name="orob2b_account_frontend_account_user_update", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountUser/Frontend:update.html.twig")
     * @Acl(
     *      id="orob2b_account_frontend_account_user_update",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUser",
     *      permission="EDIT",
     *      group_name="commerce"
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
        $form = $this->createForm(FrontendAccountUserType::NAME, $accountUser);
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

        return $result;
    }

    /**
     * @Route("/info/{id}", name="orob2b_account_frontend_account_user_info", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountUser/Frontend/widget:info.html.twig")
     * @AclAncestor("orob2b_account_account_user_view")
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
}

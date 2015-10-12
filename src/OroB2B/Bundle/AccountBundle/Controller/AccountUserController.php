<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserType;
use OroB2B\Bundle\AccountBundle\Form\Handler\AccountUserHandler;

class AccountUserController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_account_account_user_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_account_account_user_view",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUser",
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
     * @Route("/", name="orob2b_account_account_user_index")
     * @Template
     * @AclAncestor("orob2b_account_account_user_view")
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
     * @Route("/info/{id}", name="orob2b_account_account_user_info", requirements={"id"="\d+"})
     * @Template
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

    /**
     * @Route("/get-roles/with-user/{accountUserId}/{accountId}",
     *      name="orob2b_account_account_user_get_roles_with_user",
     *      requirements={"accountId"="\d+", "accountUserId"="\d+"},
     *      defaults={"accountId"="null"}
     * )
     * @Method({"GET"})
     * @Template("OroB2BAccountBundle:AccountUser:widget/roles.html.twig")
     * @AclAncestor("orob2b_account_account_user_view")
     *
     * @ParamConverter("accountUser", class="OroB2BAccountBundle:AccountUser", options={"id" = "accountUserId"})
     * @ParamConverter("account", class="OroB2BAccountBundle:Account", options={"id" = "accountId"})
     *
     * @param AccountUser $accountUser
     * @param Account     $account
     * @param Request     $request
     * @return array
     */
    public function getRolesWithUserAction(Request $request, AccountUser $accountUser, Account $account = null)
    {
        return $this->getRoles($request, $accountUser, $account);
    }

    /**
     * @Route("/get-roles/with-account/{accountId}",
     *      name="orob2b_account_account_user_by_account_roles",
     *      requirements={"accountId"="\d+"},
     *      defaults={"accountId"="null"}
     * )
     * @Method({"GET"})
     * @Template("OroB2BAccountBundle:AccountUser:widget/roles.html.twig")
     * @AclAncestor("orob2b_account_account_user_view")
     *
     * @ParamConverter("account", class="OroB2BAccountBundle:Account", options={"id" = "accountId"})
     *
     * @param Account $account
     * @param Request $request
     * @return array
     */
    public function getRolesWithAccountAction(Request $request, Account $account = null)
    {
        $accountUser = new AccountUser();

        return $this->getRoles($request, $accountUser, $account);
    }

    /**
     * Create account user form
     *
     * @Route("/create", name="orob2b_account_account_user_create")
     * @Template("OroB2BAccountBundle:AccountUser:update.html.twig")
     * @Acl(
     *      id="orob2b_account_account_user_create",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUser",
     *      permission="CREATE"
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
     * @Route("/update/{id}", name="orob2b_account_account_user_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_account_account_user_update",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUser",
     *      permission="EDIT"
     * )
     * @param AccountUser $accountUser
     * @param Request     $request
     * @return array|RedirectResponse
     */
    public function updateAction(AccountUser $accountUser, Request $request)
    {
        return $this->update($accountUser, $request);
    }

    /**
     * @param AccountUser $accountUser
     * @param Request     $request
     * @return array|RedirectResponse
     */
    protected function update(AccountUser $accountUser, Request $request)
    {
        $form = $this->createForm(AccountUserType::NAME, $accountUser);
        $handler = new AccountUserHandler(
            $form,
            $request,
            $this->get('orob2b_account_user.manager'),
            $this->get('oro_security.security_facade')
        );

        $result = $this->get('oro_form.model.update_handler')->handleUpdate(
            $accountUser,
            $form,
            function (AccountUser $accountUser) {
                return [
                    'route'      => 'orob2b_account_account_user_update',
                    'parameters' => ['id' => $accountUser->getId()]
                ];
            },
            function (AccountUser $accountUser) {
                return [
                    'route'      => 'orob2b_account_account_user_view',
                    'parameters' => ['id' => $accountUser->getId()]
                ];
            },
            $this->get('translator')->trans('orob2b.account.controller.accountuser.saved.message'),
            $handler
        );

        return $result;
    }

    /**
     * @param Request      $request
     * @param AccountUser  $accountUser
     * @param Account|null $account
     * @return array|RedirectResponse
     */
    protected function getRoles(Request $request, AccountUser $accountUser, Account $account = null)
    {
        if ($account) {
            $accountUser->setAccount($account);
            $accountUser->setOrganization($account->getOrganization());
        } else {
            $accountUser->setAccount(null);
        }

        return $this->update($accountUser, $request);
    }
}

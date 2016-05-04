<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

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
     * @Route("/get-roles/{accountUserId}/{accountId}",
     *      name="orob2b_account_account_user_roles",
     *      requirements={"accountId"="\d+", "accountUserId"="\d+"},
     *      defaults={"accountId"=0, "accountUserId"=0}
     * )
     * @Template("OroB2BAccountBundle:AccountUser:widget/roles.html.twig")
     * @AclAncestor("orob2b_account_account_user_view")
     *
     * @param string $accountUserId
     * @param string $accountId
     * @return array
     */
    public function getRolesAction($accountUserId, $accountId)
    {
        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->get('oro_entity.doctrine_helper');

        if ($accountUserId) {
            $accountUser = $doctrineHelper->getEntityReference(
                $this->getParameter('orob2b_account.entity.account_user.class'),
                $accountUserId
            );
        } else {
            $accountUser = new AccountUser();
        }

        $account = null;
        if ($accountId) {
            $account = $doctrineHelper->getEntityReference(
                $this->getParameter('orob2b_account.entity.account.class'),
                $accountId
            );
        }
        $accountUser->setAccount($account);

        $form = $this->createForm(AccountUserType::NAME, $accountUser);

        return ['form' => $form->createView()];
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
            $this->get('oro_security.security_facade'),
            $this->get('translator'),
            $this->get('logger')
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
}

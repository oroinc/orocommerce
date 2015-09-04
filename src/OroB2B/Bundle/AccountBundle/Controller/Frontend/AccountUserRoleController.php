<?php

namespace OroB2B\Bundle\AccountBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_account_user_role_frontend_index")
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:index.html.twig")
     * @Acl(
     *      id="orob2b_account_account_user_role_frontend_index",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_account.entity.account_user_role.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_account_account_user_role_frontend_view", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountUserRole/Frontend:view.html.twig")
     * TODO: Add @ to Acl after merge BB-867
     * Acl(
     *      id="orob2b_account_account_user_role_frontend_view",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="FRONTEND_ACCOUNT_ROLE_VIEW",
     *      group_name="commerce"
     * )
     *
     * @param AccountUserRole $accountUserRole
     *
     * @return array
     */
    public function viewAction(AccountUserRole $accountUserRole)
    {
        return [
            'entity' => $accountUserRole
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_account_account_user_role_info", requirements={"id"="\d+"})
     * @Template("OroB2BAccountBundle:AccountUserRole/widget:info.html.twig")
     * @Acl(
     *      id="orob2b_account_account_user_role_frontend_view",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     * @param AccountUserRole $role
     * @return array
     */
    public function infoAction(AccountUserRole $role)
    {
        return [
            'entity' => $role
        ];
    }
}

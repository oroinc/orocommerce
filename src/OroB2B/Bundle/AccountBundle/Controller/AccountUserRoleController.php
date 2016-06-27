<?php

namespace OroB2B\Bundle\AccountBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleController extends Controller
{
    /**
     * @Route("/", name="orob2b_account_account_user_role_index")
     * @Template
     * @AclAncestor("orob2b_account_account_user_role_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_account.entity.account_user_role.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="orob2b_account_account_user_role_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_account_account_user_role_view",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="VIEW"
     * )
     *
     * @param AccountUserRole $role
     * @return array
     */
    public function viewAction(AccountUserRole $role)
    {
        return [
            'entity' => $role,
            'tabsOptions' => [
                'data' => $this->getTabListOptions()
            ],
            'capabilitySetOptions' => [
                'data' => $this->getRolePrivilegeCapabilityProvider()->getCapabilities($role),
                'tabIds' => $this->getRolePrivilegeCategoryProvider()->getTabList()
            ]
        ];
    }

    /**
     * @Route("/create", name="orob2b_account_account_user_role_create")
     * @Template("OroB2BAccountBundle:AccountUserRole:update.html.twig")
     * @Acl(
     *      id="orob2b_account_account_user_role_create",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        $roleClass = $this->container->getParameter('orob2b_account.entity.account_user_role.class');

        return $this->update(new $roleClass());
    }

    /**
     * @Route("/update/{id}", name="orob2b_account_account_user_role_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orob2b_account_account_user_role_update",
     *      type="entity",
     *      class="OroB2BAccountBundle:AccountUserRole",
     *      permission="EDIT"
     * )
     *
     * @param AccountUserRole $role
     * @return array
     */
    public function updateAction(AccountUserRole $role)
    {
        return $this->update($role);
    }

    /**
     * @param AccountUserRole $role
     * @return array|RedirectResponse
     */
    protected function update(AccountUserRole $role)
    {
        $handler = $this->get('orob2b_account.form.handler.update_account_user_role');
        $handler->createForm($role);

        if ($handler->process($role)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('orob2b.account.controller.accountuserrole.saved.message')
            );

            return $this->get('oro_ui.router')->redirect($role);
        } else {
            return [
                'entity' => $role,
                'form' => $handler->createView(),
                'tabsOptions' => [
                    'data' => $this->getTabListOptions()
                ],
                'capabilitySetOptions' => [
                    'data' => $this->getRolePrivilegeCapabilityProvider()->getCapabilities($role),
                    'tabIds' => $this->getRolePrivilegeCategoryProvider()->getTabList()
                ]
            ];
        }
    }

    /**
     * @return RolePrivilegeCategoryProvider
     */
    protected function getRolePrivilegeCategoryProvider()
    {
        return $this->get('oro_user.provider.role_privilege_category_provider');
    }

    /**
     * @return RolePrivilegeCapabilityProvider
     */
    protected function getRolePrivilegeCapabilityProvider()
    {
        return $this->get('oro_user.provider.role_privilege_capability_provider');
    }

    /**
     * @return array
     */
    protected function getTabListOptions()
    {
        return array_map(
            function (PrivilegeCategory $tab) {
                return [
                    'id' => $tab->getId(),
                    'label' => $this->get('translator')->trans($tab->getLabel())
                ];
            },
            $this->getRolePrivilegeCategoryProvider()->getTabbedCategories()
        );
    }
}

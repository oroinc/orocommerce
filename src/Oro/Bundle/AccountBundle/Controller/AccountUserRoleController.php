<?php

namespace Oro\Bundle\AccountBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\UserBundle\Model\PrivilegeCategory;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCapabilityProvider;
use Oro\Bundle\UserBundle\Provider\RolePrivilegeCategoryProvider;
use Oro\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleController extends Controller
{
    /**
     * @Route("/", name="oro_account_account_user_role_index")
     * @Template
     * @AclAncestor("oro_account_account_user_role_view")
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_account.entity.account_user_role.class')
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_account_account_user_role_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_account_account_user_role_view",
     *      type="entity",
     *      class="OroAccountBundle:AccountUserRole",
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
                'tabIds' => $this->getRolePrivilegeCategoryProvider()->getTabList(),
                'readonly' => true
            ]
        ];
    }

    /**
     * @Route("/create", name="oro_account_account_user_role_create")
     * @Template("OroAccountBundle:AccountUserRole:update.html.twig")
     * @Acl(
     *      id="oro_account_account_user_role_create",
     *      type="entity",
     *      class="OroAccountBundle:AccountUserRole",
     *      permission="CREATE"
     * )
     *
     * @return array
     */
    public function createAction()
    {
        $roleClass = $this->container->getParameter('oro_account.entity.account_user_role.class');

        return $this->update(new $roleClass());
    }

    /**
     * @Route("/update/{id}", name="oro_account_account_user_role_update", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="oro_account_account_user_role_update",
     *      type="entity",
     *      class="OroAccountBundle:AccountUserRole",
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
        $handler = $this->get('oro_account.form.handler.update_account_user_role');
        $handler->createForm($role);

        if ($handler->process($role)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('oro.account.controller.accountuserrole.saved.message')
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
